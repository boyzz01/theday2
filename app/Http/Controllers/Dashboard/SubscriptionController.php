<?php

// app/Http/Controllers/Dashboard/SubscriptionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Services\MayarService;
use App\Services\PaymentActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly MayarService $mayarService,
        private readonly PaymentActivationService $activationService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        Transaction::with('plan', 'user')
            ->where('user_id', $user->id)
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('payment_gateway_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->each(fn ($t) => $this->activationService->verifyAndActivate($t));

        $sub  = $user->fresh()->activeSubscription;
        $plan = $sub?->plan;

        $transactions = Transaction::where('user_id', $user->id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'invoice_number' => $t->invoice_number,
                'plan_name'      => $t->plan?->name ?? 'Add-on',
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
                'name'           => $plan->name,
                'slug'           => $plan->slug,
                'is_premium'     => $plan->slug === 'premium',
                'expires_at'     => $sub?->expires_at?->format('d M Y'),
                'days_remaining' => $sub ? $sub->daysRemaining() : null,
            ] : [
                'name'           => 'Gratis',
                'slug'           => 'free',
                'is_premium'     => false,
                'expires_at'     => null,
                'days_remaining' => null,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = Plan::where('slug', 'premium')->firstOrFail();

        $sub = $user->activeSubscription;
        if ($sub && $sub->plan->slug === 'premium' && $sub->daysRemaining() > 14) {
            return response()->json(['error' => 'Paket kamu masih aktif lebih dari 14 hari.'], 422);
        }

        $existing = Transaction::where('user_id', $user->id)
            ->where('status', PaymentStatus::Pending)
            ->where('plan_id', $plan->id)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            $transaction = $existing;
        } else {
            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'plan_id'        => $plan->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount'         => $plan->price,
                'payment_method' => PaymentMethod::Mayar,
                'status'         => PaymentStatus::Pending,
            ]);
        }

        try {
            $result = $this->mayarService->createInvoice($transaction, $user, 'Paket Premium TheDay (90 hari)');
            $transaction->update(['payment_gateway_id' => $result['mayar_invoice_id']]);

            return response()->json(['payment_url' => $result['payment_url']]);
        } catch (\Exception $e) {
            Log::error('Mayar checkout failed', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'user_id'        => $user->id,
            ]);

            return response()->json(['error' => 'Gagal memproses pembayaran. Silakan coba lagi.'], 500);
        }
    }

    public function invoice(Transaction $transaction): \Illuminate\Http\Response
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->view('invoices.show', [
            'transaction' => $transaction->load('plan', 'user'),
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $today = now()->format('Ymd');
        $count = Transaction::whereDate('created_at', today())->count() + 1;
        return 'INV-' . $today . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
