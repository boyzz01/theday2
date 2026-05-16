<?php

// app/Http/Controllers/WebhookController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymentActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentActivationService $activationService) {}

    public function mayar(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? '';
        $data    = $payload['data']  ?? [];

        Log::info('Mayar webhook received', ['event' => $event, 'id' => $data['id'] ?? null, 'raw' => $payload]);

        if ($event !== 'payment.received') {
            Log::info('Mayar webhook ignored (unknown event)', ['event' => $event]);
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

        $activated = $this->activationService->verifyAndActivate($transaction);

        return response()->json(['status' => $activated ? 'OK' : 'not_paid']);
    }
}
