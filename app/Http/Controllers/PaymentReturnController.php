<?php

// app/Http/Controllers/PaymentReturnController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReturnController extends Controller
{
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

        return response()->json(['status' => $transaction->status->value]);
    }
}
