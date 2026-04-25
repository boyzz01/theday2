<?php

// app/Http/Controllers/PaymentReturnController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use App\Services\MayarService;
use App\Services\PaymentActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReturnController extends Controller
{
    public function __construct(
        private readonly MayarService $mayarService,
        private readonly PaymentActivationService $activationService,
    ) {}

    public function show(Request $request): Response
    {
        $transaction = Transaction::find($request->query('txn'));

        if (! $transaction || $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('PaymentReturn', [
            'transactionId' => $transaction->id,
            'status'        => $transaction->status->value,
        ]);
    }

    public function status(Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $transaction->isPaid() && $transaction->payment_gateway_id) {
            $this->syncWithMayar($transaction);
        }

        $transaction->refresh();
        return response()->json(['status' => $transaction->status->value]);
    }

    private function syncWithMayar(Transaction $transaction): void
    {
        try {
            $invoice       = $this->mayarService->getInvoice($transaction->payment_gateway_id);
            $invoiceStatus = $invoice['transactionStatus'] ?? $invoice['status'] ?? '';

            if ($invoiceStatus !== 'paid') {
                return;
            }

            DB::transaction(function () use ($transaction) {
                $transaction->update([
                    'status'  => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);

                if ($transaction->isAddonPurchase()) {
                    $this->activationService->activateAddon($transaction);
                } else {
                    $this->activationService->activatePremium($transaction);
                }
            });
        } catch (\Exception $e) {
            Log::warning('PaymentReturn: Mayar status check failed', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
