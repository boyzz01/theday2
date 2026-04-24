<?php

// app/Http/Controllers/Dashboard/AddonController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\MayarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AddonController extends Controller
{
    public function __construct(private readonly MayarService $mayarService) {}

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->activeSubscription;
        if (! $subscription || ! $subscription->isPremium()) {
            return response()->json([
                'error' => 'Tambah undangan hanya tersedia untuk pengguna Premium aktif.',
            ], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $quantity   = $validated['quantity'];
        $totalPrice = $quantity * 15000;

        $existing = Transaction::where('user_id', $user->id)
            ->where('status', PaymentStatus::Pending)
            ->where('addon_quantity', $quantity)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            $transaction = $existing;
        } else {
            $transaction = Transaction::create([
                'user_id'         => $user->id,
                'plan_id'         => null,
                'subscription_id' => $subscription->id,
                'addon_quantity'  => $quantity,
                'invoice_number'  => $this->generateInvoiceNumber(),
                'amount'          => $totalPrice,
                'payment_method'  => PaymentMethod::Mayar,
                'status'          => PaymentStatus::Pending,
            ]);
        }

        $itemLabel = $quantity === 1
            ? 'Tambah 1 Undangan (Add-on)'
            : "Tambah {$quantity} Undangan (Add-on)";

        try {
            $result = $this->mayarService->createInvoice($transaction, $user, $itemLabel);
            $transaction->update(['payment_gateway_id' => $result['mayar_invoice_id']]);

            return response()->json(['payment_url' => $result['payment_url']]);
        } catch (\Exception $e) {
            Log::error('Mayar addon checkout failed', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'user_id'        => $user->id,
            ]);

            return response()->json(['error' => 'Gagal memproses pembayaran. Silakan coba lagi.'], 500);
        }
    }

    private function generateInvoiceNumber(): string
    {
        $today = now()->format('Ymd');
        $count = Transaction::whereDate('created_at', today())->count() + 1;

        return 'INV-' . $today . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
