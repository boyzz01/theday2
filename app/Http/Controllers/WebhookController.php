<?php

// app/Http/Controllers/WebhookController.php

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

class WebhookController extends Controller
{
    public function __construct(
        private readonly MayarService $mayarService,
        private readonly PaymentActivationService $activationService,
    ) {}

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
                $this->activationService->activateAddon($transaction);
            } else {
                $this->activationService->activatePremium($transaction);
            }
        });

        return response()->json(['status' => 'OK']);
    }
}
