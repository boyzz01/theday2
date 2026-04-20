<?php

// app/Http/Controllers/Dashboard/AddonController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class AddonController extends Controller
{
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

        $quantity     = $validated['quantity'];
        $pricePerUnit = 15000;
        $totalPrice   = $quantity * $pricePerUnit;

        // Prevent duplicate pending addon order in last 24h with same quantity
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
                'payment_method'  => PaymentMethod::Midtrans,
                'status'          => PaymentStatus::Pending,
            ]);
        }

        try {
            $this->configureMidtrans();

            $itemLabel = $quantity === 1
                ? 'Tambah 1 Undangan (Add-on)'
                : "Tambah {$quantity} Undangan (Add-on)";

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id'     => $transaction->id,
                    'gross_amount' => $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email'      => $user->email,
                    'phone'      => $user->phone ?? '',
                ],
                'item_details' => [
                    [
                        'id'       => 'addon-invitation',
                        'price'    => $pricePerUnit,
                        'quantity' => $quantity,
                        'name'     => $itemLabel,
                    ],
                ],
            ]);

            $transaction->update(['payment_gateway_id' => $transaction->id]);

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans addon checkout failed', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'user_id'        => $user->id,
            ]);

            return response()->json(['error' => 'Gagal memproses pembayaran. Silakan coba lagi.'], 500);
        }
    }

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey    = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;
    }

    private function generateInvoiceNumber(): string
    {
        $today = now()->format('Ymd');
        $count = Transaction::whereDate('created_at', today())->count() + 1;

        return 'INV-' . $today . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
