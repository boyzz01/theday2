<?php

// app/Http/Controllers/WebhookController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessMail;
use App\Models\InvitationAddon;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookController extends Controller
{
    public function midtrans(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('Midtrans webhook received', ['order_id' => $data['order_id'] ?? null, 'status' => $data['transaction_status'] ?? null]);

        // ── Verify signature ─────────────────────────────────────────────────
        $serverKey         = config('midtrans.server_key');
        $expectedSignature = hash('sha512',
            ($data['order_id']      ?? '') .
            ($data['status_code']   ?? '') .
            ($data['gross_amount']  ?? '') .
            $serverKey
        );

        if ($expectedSignature !== ($data['signature_key'] ?? '')) {
            Log::warning('Midtrans webhook: invalid signature', ['order_id' => $data['order_id'] ?? null]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // ── Find transaction ──────────────────────────────────────────────────
        $transaction = Transaction::with('plan', 'user')->find($data['order_id'] ?? null);

        if (! $transaction) {
            Log::warning('Midtrans webhook: transaction not found', ['order_id' => $data['order_id'] ?? null]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // ── Handle status ─────────────────────────────────────────────────────
        $transactionStatus = $data['transaction_status'] ?? '';
        $fraudStatus       = $data['fraud_status']       ?? 'accept';

        DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $data) {
            if ($transactionStatus === 'capture') {
                $status = $fraudStatus === 'challenge' ? PaymentStatus::Pending : PaymentStatus::Paid;
            } elseif ($transactionStatus === 'settlement') {
                $status = PaymentStatus::Paid;
            } elseif (in_array($transactionStatus, ['pending'])) {
                $status = PaymentStatus::Pending;
            } else {
                // deny, cancel, expire, failure
                $status = PaymentStatus::Failed;
            }

            $transaction->update([
                'status'           => $status,
                'gateway_response' => $data,
                'paid_at'          => $status === PaymentStatus::Paid ? now() : null,
            ]);

            if ($status === PaymentStatus::Paid) {
                if ($transaction->isAddonPurchase()) {
                    $this->activateAddon($transaction);
                } else {
                    $this->activatePremium($transaction);
                }
            }
        });

        return response()->json(['status' => 'OK']);
    }

    private function activatePremium(Transaction $transaction): void
    {
        $user = $transaction->user;
        $plan = $transaction->plan;

        // Extend if already on premium with future expiry; else create new
        $existingSub = $user->activeSubscription;

        if ($existingSub && $existingSub->plan->slug === 'premium' && $existingSub->expires_at?->isFuture()) {
            $newExpiry = $existingSub->expires_at->addDays($plan->duration_days);
            $existingSub->update(['expires_at' => $newExpiry]);
            $subscription = $existingSub;
        } else {
            // Expire any active subscriptions first
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $subscription = Subscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($plan->duration_days),
            ]);
        }

        // Link transaction → subscription
        $transaction->update(['subscription_id' => $subscription->id]);

        // Send confirmation email (queued)
        Mail::to($user->email)->queue(new PaymentSuccessMail($user, $transaction, $subscription));

        Log::info('Premium activated', [
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'expires_at'      => $subscription->expires_at->toDateString(),
        ]);
    }

    private function activateAddon(Transaction $transaction): void
    {
        $user         = $transaction->user;
        $subscription = $transaction->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            Log::warning('Addon payment received but subscription not active', [
                'transaction_id'  => $transaction->id,
                'user_id'         => $user->id,
                'subscription_id' => $transaction->subscription_id,
            ]);
            return;
        }

        $addon = InvitationAddon::create([
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'quantity'        => $transaction->addon_quantity,
            'price_per_unit'  => 15000,
            'total_price'     => (int) $transaction->amount,
            'paid_at'         => now(),
            'expires_at'      => $subscription->expires_at,
        ]);

        Log::info('Invitation addon activated', [
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'addon_id'        => $addon->id,
            'quantity'        => $addon->quantity,
            'expires_at'      => $addon->expires_at?->toDateString(),
        ]);
    }
}
