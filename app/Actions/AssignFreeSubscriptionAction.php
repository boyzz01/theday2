<?php

// app/Actions/AssignFreeSubscriptionAction.php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class AssignFreeSubscriptionAction
{
    public function execute(User $user): void
    {
        $freePlan = Plan::where('name', 'Free')->where('price', 0)->firstOrFail();

        Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $freePlan->id,
            'status'     => 'active',
            'starts_at'  => now(),
            'expires_at' => null, // Free plan tidak expired
        ]);
    }
}
