<?php

// app/Services/PaymentActivationService.php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PaymentSuccessMail;
use App\Models\InvitationAddon;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentActivationService
{
    public function activatePremium(Transaction $transaction): void
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

    public function activateAddon(Transaction $transaction): void
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
