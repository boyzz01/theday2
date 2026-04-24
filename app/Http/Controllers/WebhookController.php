<?php

// app/Http/Controllers/WebhookController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessMail;
use App\Models\InvitationAddon;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\MayarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookController extends Controller
{
    public function __construct(private readonly MayarService $mayarService) {}

    public function mayar(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? '';
        $data    = $payload['data']  ?? [];

        Log::info('Mayar webhook received', ['event' => $event, 'id' => $data['id'] ?? null]);

        if ($event !== 'payment.received') {
            return response()->json(['status' => 'ignored']);
        }

        $mayarInvoiceId = $data['id'] ?? null;

        $transaction = Transaction::with('plan', 'user')
            ->where('payment_gateway_id', $mayarInvoiceId)
            ->first();

        if (! $transaction) {
            Log::warning('Mayar webhook: transaction not found', ['mayar_invoice_id' => $mayarInvoiceId]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        if ($transaction->isPaid()) {
            return response()->json(['status' => 'already_paid']);
        }

        try {
            $invoice       = $this->mayarService->getInvoice($mayarInvoiceId);
            $invoiceStatus = $invoice['transactionStatus'] ?? $invoice['status'] ?? '';
        } catch (\Exception $e) {
            Log::error('Mayar webhook: verification failed', [
                'error'            => $e->getMessage(),
                'mayar_invoice_id' => $mayarInvoiceId,
            ]);
            return response()->json(['status' => 'verification_failed']);
        }

        if ($invoiceStatus !== 'paid') {
            Log::info('Mayar webhook: invoice not paid', [
                'status'           => $invoiceStatus,
                'mayar_invoice_id' => $mayarInvoiceId,
            ]);
            return response()->json(['status' => 'not_paid']);
        }

        DB::transaction(function () use ($transaction, $payload) {
            $transaction->update([
                'status'           => PaymentStatus::Paid,
                'gateway_response' => $payload,
                'paid_at'          => now(),
            ]);

            if ($transaction->isAddonPurchase()) {
                $this->activateAddon($transaction);
            } else {
                $this->activatePremium($transaction);
            }
        });

        return response()->json(['status' => 'OK']);
    }

    private function activatePremium(Transaction $transaction): void
    {
        $user = $transaction->user;
        $plan = $transaction->plan;

        $existingSub = $user->activeSubscription;

        if ($existingSub && $existingSub->plan->slug === 'premium' && $existingSub->expires_at?->isFuture()) {
            $newExpiry = $existingSub->expires_at->addDays($plan->duration_days);
            $existingSub->update(['expires_at' => $newExpiry]);
            $subscription = $existingSub;
        } else {
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

        $transaction->update(['subscription_id' => $subscription->id]);

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
            throw new \RuntimeException('Cannot activate addon: subscription is not active.');
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
        ]);
    }
}
