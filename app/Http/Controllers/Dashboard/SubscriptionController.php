<?php

// app/Http/Controllers/Dashboard/SubscriptionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\PaymentSuccessMail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user    = $request->user();
        $sub     = $user->activeSubscription;
        $plan    = $sub?->plan;

        $transactions = Transaction::where('user_id', $user->id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'invoice_number' => $t->invoice_number,
                'plan_name'      => $t->plan->name,
                'amount'         => (int) $t->amount,
                'amount_fmt'     => 'Rp ' . number_format((int) $t->amount, 0, ',', '.'),
                'payment_method' => $t->payment_method instanceof PaymentMethod
                    ? $t->payment_method->label()
                    : ucfirst($t->payment_method ?? ''),
                'status'         => $t->status instanceof PaymentStatus
                    ? $t->status->value
                    : ($t->status ?? 'pending'),
                'status_label'   => $t->status instanceof PaymentStatus
                    ? $t->status->label()
                    : ucfirst($t->status ?? ''),
                'paid_at'        => $t->paid_at?->format('d M Y'),
                'created_at'     => $t->created_at->format('d M Y'),
            ]);

        return Inertia::render('Dashboard/Paket', [
            'currentPlan' => $plan ? [
                'name'          => $plan->name,
                'slug'          => $plan->slug,
                'is_premium'    => $plan->slug === 'premium',
                'expires_at'    => $sub?->expires_at?->format('d M Y'),
                'days_remaining' => $sub ? $sub->daysRemaining() : null,
            ] : [
                'name'          => 'Gratis',
                'slug'          => 'free',
                'is_premium'    => false,
                'expires_at'    => null,
                'days_remaining' => null,
            ],
            'transactions'        => $transactions,
            'midtransClientKey'   => config('midtrans.client_key'),
            'snapUrl'             => config('midtrans.snap_url'),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = Plan::where('slug', 'premium')->firstOrFail();

        // Block if already has active premium with > 14 days left
        $sub = $user->activeSubscription;
        if ($sub && $sub->plan->slug === 'premium' && $sub->daysRemaining() > 14) {
            return response()->json(['error' => 'Paket kamu masih aktif lebih dari 14 hari.'], 422);
        }

        // Check for an existing pending transaction in the last 24 hours (prevent double order)
        $existing = Transaction::where('user_id', $user->id)
            ->where('status', PaymentStatus::Pending)
            ->where('plan_id', $plan->id)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            $transaction = $existing;
        } else {
            $invoiceNumber = $this->generateInvoiceNumber();

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'plan_id'        => $plan->id,
                'invoice_number' => $invoiceNumber,
                'amount'         => $plan->price,
                'payment_method' => PaymentMethod::Midtrans,
                'status'         => PaymentStatus::Pending,
            ]);
        }

        try {
            $this->configureMidtrans();

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id'    => $transaction->id,
                    'gross_amount' => (int) $transaction->amount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email'      => $user->email,
                    'phone'      => $user->phone ?? '',
                ],
                'item_details' => [
                    [
                        'id'       => $plan->slug,
                        'price'    => (int) $plan->price,
                        'quantity' => 1,
                        'name'     => 'Paket Premium TheDay (30 hari)',
                    ],
                ],
            ]);

            // Store order_id reference
            $transaction->update(['payment_gateway_id' => $transaction->id]);

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans checkout failed', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'user_id'        => $user->id,
            ]);

            return response()->json(['error' => 'Gagal memproses pembayaran. Silakan coba lagi.'], 500);
        }
    }

    public function invoice(Transaction $transaction): \Illuminate\Http\Response
    {
        // Ensure owner
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->view('invoices.show', [
            'transaction' => $transaction->load('plan', 'user'),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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
