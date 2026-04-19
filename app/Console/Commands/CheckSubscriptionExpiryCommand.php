<?php

// app/Console/Commands/CheckSubscriptionExpiryCommand.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiryMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSubscriptionExpiryCommand extends Command
{
    protected $signature   = 'theday:check-subscription-expiry';
    protected $description = 'Send expiry reminder emails for Premium subscriptions nearing expiry';

    public function handle(): int
    {
        // 7-day reminders
        $this->sendReminders(7);

        // 1-day reminders
        $this->sendReminders(1);

        // Expired today — notify and mark expired
        $this->handleExpiredToday();

        return self::SUCCESS;
    }

    private function sendReminders(int $days): void
    {
        $targetDate = now()->addDays($days)->toDateString();

        Subscription::where('status', 'active')
            ->whereHas('plan', fn ($q) => $q->where('slug', 'premium'))
            ->whereDate('expires_at', $targetDate)
            ->with('user', 'plan')
            ->get()
            ->each(function (Subscription $sub) use ($days) {
                Mail::to($sub->user->email)
                    ->queue(new SubscriptionExpiryMail($sub->user, $sub, $days));

                $this->info("  Sent {$days}-day reminder to {$sub->user->email}");
            });
    }

    private function handleExpiredToday(): void
    {
        // Transition active subscriptions past expires_at → grace period
        $expired = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->with('user', 'plan')
            ->get();

        foreach ($expired as $sub) {
            $sub->update(['status' => 'grace']);

            if ($sub->plan->slug === 'premium') {
                Mail::to($sub->user->email)
                    ->queue(new SubscriptionExpiryMail($sub->user, $sub, 0));

                $this->info("  Sent expiry notice to {$sub->user->email}");
            }
        }

        if ($expired->count() > 0) {
            $this->info("  Moved {$expired->count()} subscription(s) to grace period.");
        }

        // Send grace period reminders at 30, 15, 2 days remaining
        $this->sendGraceReminders();
    }

    private function sendGraceReminders(): void
    {
        $graceSubscriptions = Subscription::where('status', 'grace')
            ->whereNotNull('grace_until')
            ->where('grace_until', '>', now())
            ->with('user', 'plan')
            ->get();

        foreach ($graceSubscriptions as $sub) {
            $daysLeft = $sub->graceDaysRemaining();

            if (in_array($daysLeft, [30, 15, 2], true)) {
                Mail::to($sub->user->email)
                    ->queue(new SubscriptionExpiryMail($sub->user, $sub, -$daysLeft));

                $this->info("  Sent grace reminder ({$daysLeft}d left) to {$sub->user->email}");
            }
        }
    }
}
