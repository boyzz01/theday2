<?php

// app/Http/Controllers/PaymentReturnController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymentActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReturnController extends Controller
{
    public function __construct(private readonly PaymentActivationService $activationService) {}

    public function show(Request $request): Response
    {
        $transaction = Transaction::with('plan', 'user')->find($request->query('txn'));

        if (! $transaction || $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        if ($transaction->isPending()) {
            $this->activationService->verifyAndActivate($transaction);
            $transaction->refresh();
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

        return response()->json(['status' => $transaction->status->value]);
    }
}
