<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use App\Services\PaymentActivationService;
use Illuminate\Console\Command;

class VerifyPendingPayments extends Command
{
    protected $signature   = 'payments:verify-pending';
    protected $description = 'Verify pending transactions against Mayar API and activate if paid';

    public function __construct(private readonly PaymentActivationService $activationService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $transactions = Transaction::with('plan', 'user')
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('payment_gateway_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No pending transactions.');
            return;
        }

        $this->info("Checking {$transactions->count()} pending transaction(s)...");

        $activated = 0;
        foreach ($transactions as $transaction) {
            if ($this->activationService->verifyAndActivate($transaction)) {
                $this->info("Activated: {$transaction->id}");
                $activated++;
            }
        }

        $this->info("Done. {$activated} activated.");
    }
}
