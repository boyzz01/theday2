<?php

// app/Observers/SubscriptionObserver.php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    public function saving(Subscription $subscription): void
    {
        if ($subscription->isDirty('expires_at') && $subscription->expires_at) {
            $subscription->grace_until = $subscription->expires_at->addDays(30);
        }
    }
}
