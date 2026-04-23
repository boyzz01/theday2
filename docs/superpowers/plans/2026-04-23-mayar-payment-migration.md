# Mayar Payment Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti payment gateway dari Midtrans Snap ke Mayar.id Invoice API (full cut-over, redirect flow).

**Architecture:** Backend buat Mayar invoice via `MayarService`, simpan Mayar invoice ID ke `transactions.payment_gateway_id`, return `payment_url` ke frontend untuk redirect. Webhook Mayar verifikasi via API call balik sebelum aktivasi subscription/addon.

**Tech Stack:** Laravel 11, Mayar Headless API v1, Inertia.js + Vue 3, PHPUnit, `Illuminate\Support\Facades\Http`

---

## File Map

| File | Action |
|---|---|
| `config/midtrans.php` | Delete |
| `config/mayar.php` | Create |
| `bootstrap/app.php` | Modify — ganti CSRF exclusion |
| `composer.json` | Modify — remove `midtrans/midtrans-php` |
| `app/Enums/PaymentMethod.php` | Modify — tambah `Mayar` case |
| `app/Services/MayarService.php` | Create |
| `app/Http/Controllers/Dashboard/SubscriptionController.php` | Modify |
| `app/Http/Controllers/Dashboard/AddonController.php` | Modify |
| `app/Http/Controllers/WebhookController.php` | Modify |
| `app/Http/Controllers/PaymentReturnController.php` | Create |
| `routes/web.php` | Modify — webhook + return routes |
| `resources/js/Pages/Dashboard/Paket.vue` | Modify |
| `resources/js/Pages/PaymentReturn.vue` | Create |
| `tests/Feature/Payment/MayarServiceTest.php` | Create |
| `tests/Feature/Payment/SubscriptionCheckoutTest.php` | Create |
| `tests/Feature/Payment/AddonCheckoutTest.php` | Create |
| `tests/Feature/Payment/MayarWebhookTest.php` | Create |
| `tests/Feature/Payment/PaymentReturnTest.php` | Create |

---

## Task 1: Remove Midtrans, Add Mayar Config

**Files:**
- Delete: `config/midtrans.php`
- Create: `config/mayar.php`
- Modify: `bootstrap/app.php`
- Modify: `composer.json` + `composer.lock`

- [ ] **Step 1: Remove Midtrans composer package**

```bash
composer remove midtrans/midtrans-php
```

Expected: Package removed, `composer.json` and `composer.lock` updated.

- [ ] **Step 2: Delete Midtrans config**

Delete `config/midtrans.php`.

- [ ] **Step 3: Create Mayar config**

Create `config/mayar.php`:

```php
<?php

return [
    'api_key'       => env('MAYAR_API_KEY', ''),
    'is_production' => env('MAYAR_IS_PRODUCTION', false),
    'base_url'      => env('MAYAR_IS_PRODUCTION', false)
        ? 'https://api.mayar.id/hl/v1'
        : 'https://api.mayar.club/hl/v1',
];
```

- [ ] **Step 4: Update CSRF exclusion in `bootstrap/app.php`**

Find the block:
```php
// Exclude Midtrans webhook from CSRF verification
$middleware->validateCsrfTokens(except: [
    'webhooks/midtrans',
    'logout',
]);
```

Replace with:
```php
// Exclude Mayar webhook from CSRF verification
$middleware->validateCsrfTokens(except: [
    'webhooks/mayar',
    'logout',
]);
```

- [ ] **Step 5: Add `.env` variables** (add to `.env` and `.env.example`)

```
MAYAR_API_KEY=
MAYAR_IS_PRODUCTION=false
```

- [ ] **Step 6: Commit**

```bash
git add config/mayar.php bootstrap/app.php composer.json composer.lock .env.example
git rm config/midtrans.php
git commit -m "chore: remove Midtrans, add Mayar config"
```

---

## Task 2: Update PaymentMethod Enum

**Files:**
- Modify: `app/Enums/PaymentMethod.php`
- Create: `tests/Feature/Payment/` (directory)

- [ ] **Step 1: Create test directory and write failing test**

Create `tests/Feature/Payment/PaymentMethodTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentMethod;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_mayar_enum_value_is_mayar_string(): void
    {
        $this->assertSame('mayar', PaymentMethod::Mayar->value);
    }

    public function test_mayar_label_is_mayar(): void
    {
        $this->assertSame('Mayar', PaymentMethod::Mayar->label());
    }

    public function test_midtrans_still_exists_for_historical_data(): void
    {
        $this->assertSame('midtrans', PaymentMethod::Midtrans->value);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Payment/PaymentMethodTest.php
```

Expected: FAIL — `PaymentMethod::Mayar` does not exist.

- [ ] **Step 3: Update `app/Enums/PaymentMethod.php`**

Replace the entire file:

```php
<?php

// app/Enums/PaymentMethod.php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Mayar    = 'mayar';
    case Midtrans = 'midtrans'; // keep for historical records
    case Xendit   = 'xendit';

    public function label(): string
    {
        return match($this) {
            self::Mayar    => 'Mayar',
            self::Midtrans => 'Midtrans',
            self::Xendit   => 'Xendit',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Feature/Payment/PaymentMethodTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/PaymentMethod.php tests/Feature/Payment/PaymentMethodTest.php
git commit -m "feat: add Mayar to PaymentMethod enum"
```

---

## Task 3: Create MayarService — createInvoice

**Files:**
- Create: `app/Services/MayarService.php`
- Create: `tests/Feature/Payment/MayarServiceTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Payment/MayarServiceTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\MayarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MayarServiceTest extends TestCase
{
    use RefreshDatabase;

    private MayarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MayarService();
    }

    public function test_create_invoice_calls_mayar_api_and_returns_payment_url(): void
    {
        $user = User::factory()->create(['name' => 'Budi', 'email' => 'budi@test.com', 'phone' => '081234567890']);
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $transaction = Transaction::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'invoice_number' => 'INV-20260423-001',
            'amount'         => 35000,
            'payment_method' => PaymentMethod::Mayar,
            'status'         => PaymentStatus::Pending,
        ]);

        Http::fake([
            config('mayar.base_url') . '/invoice/create' => Http::response([
                'statusCode' => 200,
                'messages'   => 'success',
                'data'       => [
                    'id'   => 'mayar-invoice-abc123',
                    'link' => 'https://pay.mayar.id/invoices/abc123',
                ],
            ]),
        ]);

        $result = $this->service->createInvoice($transaction, $user, 'Paket Premium TheDay (90 hari)');

        $this->assertSame('https://pay.mayar.id/invoices/abc123', $result['payment_url']);
        $this->assertSame('mayar-invoice-abc123', $result['mayar_invoice_id']);

        Http::assertSent(function ($request) use ($user, $transaction) {
            $body = $request->data();
            return $request->url() === config('mayar.base_url') . '/invoice/create'
                && $body['email'] === $user->email
                && $body['name']  === $user->name
                && str_contains($body['redirectUrl'], $transaction->id);
        });
    }

    public function test_create_invoice_throws_on_api_error(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $transaction = Transaction::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'invoice_number' => 'INV-20260423-002',
            'amount'         => 35000,
            'payment_method' => PaymentMethod::Mayar,
            'status'         => PaymentStatus::Pending,
        ]);

        Http::fake([
            config('mayar.base_url') . '/invoice/create' => Http::response([], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->createInvoice($transaction, $user, 'Paket Premium TheDay (90 hari)');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Payment/MayarServiceTest.php
```

Expected: FAIL — `MayarService` class not found.

- [ ] **Step 3: Create `app/Services/MayarService.php`**

```php
<?php

// app/Services/MayarService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class MayarService
{
    public function createInvoice(Transaction $transaction, User $user, string $itemName): array
    {
        $quantity = $transaction->addon_quantity ?? 1;
        $rate     = $transaction->addon_quantity ? 15000 : (int) $transaction->amount;

        $response = Http::withToken(config('mayar.api_key'))
            ->post(config('mayar.base_url') . '/invoice/create', [
                'name'        => $user->name,
                'email'       => $user->email,
                'mobile'      => $user->phone ?? '',
                'redirectUrl' => url('/payment/return') . '?txn=' . $transaction->id,
                'expiredAt'   => now()->addHours(24)->utc()->toIso8601String(),
                'items'       => [
                    [
                        'quantity'    => $quantity,
                        'rate'        => $rate,
                        'description' => $itemName,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Mayar API error: ' . $response->body());
        }

        $data = $response->json('data');

        return [
            'payment_url'      => $data['link'],
            'mayar_invoice_id' => $data['id'],
        ];
    }

    public function getInvoice(string $mayarInvoiceId): array
    {
        $response = Http::withToken(config('mayar.api_key'))
            ->get(config('mayar.base_url') . '/invoice/' . $mayarInvoiceId);

        if (! $response->successful()) {
            throw new \RuntimeException('Mayar API error: ' . $response->body());
        }

        return $response->json('data') ?? [];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Payment/MayarServiceTest.php
```

Expected: 2 tests PASS.

- [ ] **Step 5: Add getInvoice test to `MayarServiceTest.php`**

Add this method to the test class:

```php
public function test_get_invoice_returns_data_array(): void
{
    Http::fake([
        config('mayar.base_url') . '/invoice/mayar-uuid-abc' => Http::response([
            'data' => ['transactionStatus' => 'paid', 'id' => 'mayar-uuid-abc'],
        ]),
    ]);

    $result = $this->service->getInvoice('mayar-uuid-abc');

    $this->assertSame('paid', $result['transactionStatus']);
}

public function test_get_invoice_throws_on_api_error(): void
{
    Http::fake([
        config('mayar.base_url') . '/invoice/bad-id' => Http::response([], 404),
    ]);

    $this->expectException(\RuntimeException::class);
    $this->service->getInvoice('bad-id');
}
```

- [ ] **Step 6: Run all MayarService tests**

```bash
php artisan test tests/Feature/Payment/MayarServiceTest.php
```

Expected: 4 tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/MayarService.php tests/Feature/Payment/MayarServiceTest.php
git commit -m "feat: add MayarService with createInvoice and getInvoice"
```

---

## Task 4: Update SubscriptionController

**Files:**
- Modify: `app/Http/Controllers/Dashboard/SubscriptionController.php`
- Create: `tests/Feature/Payment/SubscriptionCheckoutTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Payment/SubscriptionCheckoutTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plan = Plan::create([
            'name'          => 'Premium',
            'slug'          => 'premium',
            'price'         => 35000,
            'duration_days' => 90,
        ]);
    }

    public function test_checkout_returns_payment_url(): void
    {
        $user = User::factory()->create();

        Http::fake([
            config('mayar.base_url') . '/invoice/create' => Http::response([
                'statusCode' => 200,
                'data'       => ['id' => 'mayar-uuid-sub-001', 'link' => 'https://pay.mayar.id/invoices/sub001'],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('dashboard.subscriptions.checkout'));

        $response->assertOk()
            ->assertJsonStructure(['payment_url'])
            ->assertJsonPath('payment_url', 'https://pay.mayar.id/invoices/sub001');

        $this->assertDatabaseHas('transactions', [
            'user_id'            => $user->id,
            'payment_gateway_id' => 'mayar-uuid-sub-001',
            'status'             => 'pending',
        ]);
    }

    public function test_checkout_blocked_when_premium_has_more_than_14_days(): void
    {
        $user = User::factory()->create();

        \App\Models\Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'starts_at'  => now()->subDays(1),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('dashboard.subscriptions.checkout'));

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Paket kamu masih aktif lebih dari 14 hari.');
    }

    public function test_checkout_returns_error_when_mayar_api_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            config('mayar.base_url') . '/invoice/create' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('dashboard.subscriptions.checkout'));

        $response->assertStatus(500)
            ->assertJsonPath('error', 'Gagal memproses pembayaran. Silakan coba lagi.');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Payment/SubscriptionCheckoutTest.php
```

Expected: FAIL — response contains `snap_token` not `payment_url`.

- [ ] **Step 3: Update `SubscriptionController`**

Replace the full file:

```php
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
use App\Services\MayarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(private readonly MayarService $mayarService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sub  = $user->activeSubscription;
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
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Payment/SubscriptionCheckoutTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dashboard/SubscriptionController.php tests/Feature/Payment/SubscriptionCheckoutTest.php
git commit -m "feat: replace Midtrans with Mayar in SubscriptionController"
```

---

## Task 5: Update AddonController

**Files:**
- Modify: `app/Http/Controllers/Dashboard/AddonController.php`
- Create: `tests/Feature/Payment/AddonCheckoutTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Payment/AddonCheckoutTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddonCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;
    private User $user;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plan = Plan::create([
            'name'          => 'Premium',
            'slug'          => 'premium',
            'price'         => 35000,
            'duration_days' => 90,
        ]);
        $this->user = User::factory()->create();
        $this->subscription = Subscription::create([
            'user_id'    => $this->user->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'starts_at'  => now()->subDays(1),
            'expires_at' => now()->addDays(60),
        ]);
    }

    public function test_addon_checkout_returns_payment_url(): void
    {
        Http::fake([
            config('mayar.base_url') . '/invoice/create' => Http::response([
                'statusCode' => 200,
                'data'       => ['id' => 'mayar-uuid-addon-001', 'link' => 'https://pay.mayar.id/invoices/addon001'],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboard.addons.checkout'), ['quantity' => 3]);

        $response->assertOk()
            ->assertJsonPath('payment_url', 'https://pay.mayar.id/invoices/addon001');

        $this->assertDatabaseHas('transactions', [
            'user_id'            => $this->user->id,
            'addon_quantity'     => 3,
            'amount'             => 45000,
            'payment_gateway_id' => 'mayar-uuid-addon-001',
            'status'             => 'pending',
        ]);
    }

    public function test_addon_checkout_blocked_for_non_premium_user(): void
    {
        $freeUser = User::factory()->create();

        $response = $this->actingAs($freeUser)
            ->postJson(route('dashboard.addons.checkout'), ['quantity' => 1]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Payment/AddonCheckoutTest.php
```

Expected: FAIL — response contains `snap_token` not `payment_url`.

- [ ] **Step 3: Replace full `AddonController`**

```php
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
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Payment/AddonCheckoutTest.php
```

Expected: 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dashboard/AddonController.php tests/Feature/Payment/AddonCheckoutTest.php
git commit -m "feat: replace Midtrans with Mayar in AddonController"
```

---

## Task 6: Update WebhookController

**Files:**
- Modify: `app/Http/Controllers/WebhookController.php`
- Create: `tests/Feature/Payment/MayarWebhookTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Payment/MayarWebhookTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MayarWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingTransaction(User $user, Plan $plan, string $mayarInvoiceId): Transaction
    {
        return Transaction::create([
            'user_id'            => $user->id,
            'plan_id'            => $plan->id,
            'invoice_number'     => 'INV-20260423-001',
            'amount'             => 35000,
            'payment_method'     => PaymentMethod::Mayar,
            'payment_gateway_id' => $mayarInvoiceId,
            'status'             => PaymentStatus::Pending,
        ]);
    }

    public function test_payment_received_event_activates_premium(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $this->makePendingTransaction($user, $plan, 'mayar-uuid-wh-001');

        Http::fake([
            config('mayar.base_url') . '/invoice/mayar-uuid-wh-001' => Http::response([
                'data' => ['transactionStatus' => 'paid'],
            ]),
        ]);

        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.received',
            'data'  => ['id' => 'mayar-uuid-wh-001', 'transactionStatus' => 'paid'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'OK');

        $this->assertDatabaseHas('transactions', [
            'payment_gateway_id' => 'mayar-uuid-wh-001',
            'status'             => 'paid',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status'  => 'active',
        ]);
    }

    public function test_non_payment_received_events_are_ignored(): void
    {
        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.reminder',
            'data'  => ['id' => 'some-id'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ignored');
    }

    public function test_webhook_with_unknown_transaction_returns_404(): void
    {
        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.received',
            'data'  => ['id' => 'non-existent-mayar-id', 'transactionStatus' => 'paid'],
        ]);

        $response->assertNotFound();
    }

    public function test_webhook_is_idempotent_for_already_paid_transaction(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $transaction = $this->makePendingTransaction($user, $plan, 'mayar-uuid-wh-002');
        $transaction->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.received',
            'data'  => ['id' => 'mayar-uuid-wh-002', 'transactionStatus' => 'paid'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'already_paid');

        Http::assertNothingSent();
    }

    public function test_webhook_returns_200_when_mayar_verification_says_not_paid(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $this->makePendingTransaction($user, $plan, 'mayar-uuid-wh-003');

        Http::fake([
            config('mayar.base_url') . '/invoice/mayar-uuid-wh-003' => Http::response([
                'data' => ['transactionStatus' => 'unpaid'],
            ]),
        ]);

        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.received',
            'data'  => ['id' => 'mayar-uuid-wh-003', 'transactionStatus' => 'paid'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'not_paid');

        $this->assertDatabaseHas('transactions', [
            'payment_gateway_id' => 'mayar-uuid-wh-003',
            'status'             => 'pending',
        ]);
    }

    public function test_webhook_activates_addon_for_addon_transactions(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'slug' => 'premium', 'price' => 35000, 'duration_days' => 90]);
        $subscription = Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'status'     => 'active',
            'starts_at'  => now()->subDays(1),
            'expires_at' => now()->addDays(60),
        ]);

        $transaction = Transaction::create([
            'user_id'            => $user->id,
            'plan_id'            => null,
            'subscription_id'    => $subscription->id,
            'addon_quantity'     => 2,
            'invoice_number'     => 'INV-20260423-002',
            'amount'             => 30000,
            'payment_method'     => PaymentMethod::Mayar,
            'payment_gateway_id' => 'mayar-uuid-addon-wh',
            'status'             => PaymentStatus::Pending,
        ]);

        Http::fake([
            config('mayar.base_url') . '/invoice/mayar-uuid-addon-wh' => Http::response([
                'data' => ['transactionStatus' => 'paid'],
            ]),
        ]);

        $response = $this->postJson('/webhooks/mayar', [
            'event' => 'payment.received',
            'data'  => ['id' => 'mayar-uuid-addon-wh', 'transactionStatus' => 'paid'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'OK');

        $this->assertDatabaseHas('invitation_addons', [
            'user_id'  => $user->id,
            'quantity' => 2,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/Payment/MayarWebhookTest.php
```

Expected: FAIL — route `/webhooks/mayar` does not exist yet.

- [ ] **Step 3: Replace WebhookController**

Replace full `app/Http/Controllers/WebhookController.php`:

```php
<?php

// app/Http/Controllers/WebhookController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessMail;
use App\Models\InvitationAddon;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\MayarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookController extends Controller
{
    public function __construct(private readonly MayarService $mayarService) {}

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
                'error'           => $e->getMessage(),
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
                $this->activateAddon($transaction);
            } else {
                $this->activatePremium($transaction);
            }
        });

        return response()->json(['status' => 'OK']);
    }

    private function activatePremium(Transaction $transaction): void
    {
        $user = $transaction->user;
        $plan = $transaction->plan;

        $existingSub = $user->activeSubscription;

        if ($existingSub && $existingSub->plan->slug === 'premium' && $existingSub->expires_at?->isFuture()) {
            $newExpiry = $existingSub->expires_at->addDays($plan->duration_days);
            $existingSub->update(['expires_at' => $newExpiry]);
            $subscription = $existingSub;
        } else {
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $subscription = Subscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($plan->duration_days),
            ]);
        }

        $transaction->update(['subscription_id' => $subscription->id]);

        Mail::to($user->email)->queue(new PaymentSuccessMail($user, $transaction, $subscription));

        Log::info('Premium activated', [
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'expires_at'      => $subscription->expires_at->toDateString(),
        ]);
    }

    private function activateAddon(Transaction $transaction): void
    {
        $user         = $transaction->user;
        $subscription = $transaction->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            Log::warning('Addon payment received but subscription not active', [
                'transaction_id'  => $transaction->id,
                'user_id'         => $user->id,
                'subscription_id' => $transaction->subscription_id,
            ]);
            return;
        }

        $addon = InvitationAddon::create([
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'quantity'        => $transaction->addon_quantity,
            'price_per_unit'  => 15000,
            'total_price'     => (int) $transaction->amount,
            'paid_at'         => now(),
            'expires_at'      => $subscription->expires_at,
        ]);

        Log::info('Invitation addon activated', [
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'addon_id'        => $addon->id,
            'quantity'        => $addon->quantity,
        ]);
    }
}
```

- [ ] **Step 4: Add webhook route to `routes/web.php`**

Find:
```php
Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans'])->name('webhooks.midtrans');
```

Replace with:
```php
Route::post('/webhooks/mayar', [WebhookController::class, 'mayar'])->name('webhooks.mayar');
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Payment/MayarWebhookTest.php
```

Expected: 6 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/WebhookController.php routes/web.php tests/Feature/Payment/MayarWebhookTest.php
git commit -m "feat: replace Midtrans webhook with Mayar webhook handler"
```

---

## Task 7: PaymentReturnController + Routes

**Files:**
- Create: `app/Http/Controllers/PaymentReturnController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Payment/PaymentReturnTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Payment/PaymentReturnTest.php`:

```php
<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReturnTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user, PaymentStatus $status): Transaction
    {
        $plan = Plan::firstOrCreate(
            ['slug' => 'premium'],
            ['name' => 'Premium', 'price' => 35000, 'duration_days' => 90]
        );

        return Transaction::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'invoice_number' => 'INV-20260423-001',
            'amount'         => 35000,
            'payment_method' => PaymentMethod::Mayar,
            'status'         => $status,
        ]);
    }

    public function test_return_page_renders_for_authenticated_owner(): void
    {
        $user        = User::factory()->create();
        $transaction = $this->makeTransaction($user, PaymentStatus::Pending);

        $response = $this->actingAs($user)
            ->get('/payment/return?txn=' . $transaction->id);

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PaymentReturn')
                ->has('transactionId')
                ->has('status')
            );
    }

    public function test_return_page_returns_403_for_wrong_user(): void
    {
        $owner       = User::factory()->create();
        $otherUser   = User::factory()->create();
        $transaction = $this->makeTransaction($owner, PaymentStatus::Pending);

        $response = $this->actingAs($otherUser)
            ->get('/payment/return?txn=' . $transaction->id);

        $response->assertForbidden();
    }

    public function test_status_endpoint_returns_current_status(): void
    {
        $user        = User::factory()->create();
        $transaction = $this->makeTransaction($user, PaymentStatus::Pending);

        $response = $this->actingAs($user)
            ->getJson('/payment/transactions/' . $transaction->id . '/status');

        $response->assertOk()
            ->assertJsonPath('status', 'pending');
    }

    public function test_status_endpoint_returns_403_for_wrong_user(): void
    {
        $owner       = User::factory()->create();
        $otherUser   = User::factory()->create();
        $transaction = $this->makeTransaction($owner, PaymentStatus::Pending);

        $response = $this->actingAs($otherUser)
            ->getJson('/payment/transactions/' . $transaction->id . '/status');

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/Payment/PaymentReturnTest.php
```

Expected: FAIL — routes not found.

- [ ] **Step 3: Create `app/Http/Controllers/PaymentReturnController.php`**

```php
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
```

- [ ] **Step 4: Add routes to `routes/web.php`**

Add import at the top of `routes/web.php` (with other `use` statements):
```php
use App\Http\Controllers\PaymentReturnController;
```

Add a new route group **outside** the `dashboard.` prefix group (payment return must NOT carry the `dashboard.` name prefix):

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment/return',                            [PaymentReturnController::class, 'show']  )->name('payment.return');
    Route::get('/payment/transactions/{transaction}/status', [PaymentReturnController::class, 'status'])->name('payment.status');
});
```

Place this block near the other auth-only routes, before the final webhook route.

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Payment/PaymentReturnTest.php
```

Expected: 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PaymentReturnController.php routes/web.php tests/Feature/Payment/PaymentReturnTest.php
git commit -m "feat: add PaymentReturnController with status polling endpoint"
```

---

## Task 8: Update Paket.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Paket.vue`

No automated test — verify manually in browser after `npm run dev`.

- [ ] **Step 1: Replace `<script setup>` section in Paket.vue**

Replace lines 1–125 (entire `<script setup>`) with:

```vue
<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    currentPlan:  { type: Object, required: true },
    transactions: { type: Array,  default: () => [] },
});

// ── Checkout state ────────────────────────────────────────────────────────────
const isCheckingOut = ref(false);
const checkoutError = ref('');

// ── FAQ accordion ─────────────────────────────────────────────────────────────
const openFaq = ref(null);
const faqs = [
    {
        q: 'Apakah Premium otomatis diperpanjang?',
        a: 'Tidak. Premium adalah pembayaran satu kali untuk 3 bulan (90 hari). Tidak ada auto-renewal. Kamu bisa perpanjang manual kapan saja.',
    },
    {
        q: 'Bagaimana cara pembayaran?',
        a: 'Kamu bisa bayar via GoPay, OVO, Dana, QRIS, transfer bank virtual, atau kartu kredit melalui Mayar — aman dan terenkripsi.',
    },
    {
        q: 'Ada garansi uang kembali?',
        a: 'Ada. Jika tidak puas dalam 7 hari pertama, kami kembalikan 100% pembayaranmu. Hubungi hello@theday.id.',
    },
    {
        q: 'Kalau Premium habis, undangan saya hilang?',
        a: 'Tidak. Undangan kamu tetap ada dan bisa diakses. Namun beberapa fitur Premium tidak aktif lagi sampai kamu perpanjang.',
    },
    {
        q: 'Bisa upgrade di tengah jalan?',
        a: 'Bisa kapan saja. Kamu langsung dapat akses Premium setelah pembayaran berhasil.',
    },
];

// ── Computed ──────────────────────────────────────────────────────────────────
const isPremium    = computed(() => props.currentPlan.is_premium);
const daysLeft     = computed(() => props.currentPlan.days_remaining ?? null);
const expiresAt    = computed(() => props.currentPlan.expires_at);
const expiryWarn   = computed(() => isPremium.value && daysLeft.value !== null && daysLeft.value <= 7);

const premiumCtaLabel = computed(() => {
    if (!isPremium.value) return 'Upgrade Sekarang →';
    if (daysLeft.value !== null && daysLeft.value <= 14) return 'Perpanjang →';
    return 'Sudah Premium ✓';
});
const premiumCtaDisabled = computed(() => isPremium.value && daysLeft.value !== null && daysLeft.value > 14);

// ── Checkout ──────────────────────────────────────────────────────────────────
const startCheckout = async () => {
    if (isCheckingOut.value || premiumCtaDisabled.value) return;

    isCheckingOut.value = true;
    checkoutError.value = '';

    try {
        const { data } = await axios.post(route('dashboard.subscriptions.checkout'));
        window.location.href = data.payment_url;
    } catch (err) {
        checkoutError.value = err?.response?.data?.error ?? 'Terjadi kesalahan. Silakan coba lagi.';
        isCheckingOut.value = false;
    }
};

// ── Feature comparison rows ───────────────────────────────────────────────────
const features = [
    { label: 'Undangan aktif',      free: '1',  premium: 'Unlimited' },
    { label: 'Foto per undangan',   free: '5',  premium: 'Unlimited' },
    { label: 'Template premium',    free: false, premium: true },
    { label: 'Upload musik sendiri',free: false, premium: true },
    { label: 'Tanpa watermark',     free: false, premium: true },
    { label: 'Custom URL slug',     free: false, premium: true },
    { label: 'Password protection', free: false, premium: true },
    { label: 'Analytics kunjungan', free: false, premium: true },
    { label: 'Prioritas dukungan',  free: false, premium: true },
];
</script>
```

- [ ] **Step 2: Update checkout state notice block in template**

Find lines 191–202 (the v-if state notices block):

```html
<!-- Checkout state notices -->
<div v-if="checkoutState === 'success'"
     class="rounded-xl p-4 text-sm font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
    ✓ Pembayaran berhasil! Paket Premium kamu sudah aktif.
</div>
<div v-if="checkoutState === 'pending'"
     class="rounded-xl p-4 text-sm bg-[#92A89C]/10 text-[#2C2417] border border-[#B8C7BF]/50">
    ⏳ Pembayaran sedang diproses. Halaman akan diperbarui otomatis.
</div>
<div v-if="checkoutState === 'error'"
     class="rounded-xl p-4 text-sm bg-red-50 text-red-700 border border-red-100">
    {{ checkoutError }}
</div>
```

Replace with:

```html
<!-- Checkout error notice -->
<div v-if="checkoutError"
     class="rounded-xl p-4 text-sm bg-red-50 text-red-700 border border-red-100">
    {{ checkoutError }}
</div>
```

- [ ] **Step 3: Update button disabled condition in template**

Find both occurrences of `:disabled="premiumCtaDisabled || isCheckingOut"` — they remain valid as-is.

Find `{{ isCheckingOut ? 'Memproses...' : (isPremium ? 'Perpanjang Premium →' : 'Upgrade ke Premium →') }}` (line ~185) — update to:
```html
{{ isCheckingOut ? 'Mengarahkan ke pembayaran...' : (isPremium ? 'Perpanjang Premium →' : 'Upgrade ke Premium →') }}
```

Find `{{ isCheckingOut ? 'Memproses...' : premiumCtaLabel }}` (line ~288) — update to:
```html
{{ isCheckingOut ? 'Mengarahkan...' : premiumCtaLabel }}
```

- [ ] **Step 4: Verify build has no errors**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build completes without errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Dashboard/Paket.vue
git commit -m "feat: update Paket.vue - remove Snap.js, add Mayar redirect checkout"
```

---

## Task 9: Create PaymentReturn.vue

**Files:**
- Create: `resources/js/Pages/PaymentReturn.vue`

- [ ] **Step 1: Create `resources/js/Pages/PaymentReturn.vue`**

```vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    transactionId: { type: String, required: true },
    status:        { type: String, required: true },
});

const currentStatus = ref(props.status);
const pollCount     = ref(0);
const maxed         = ref(false);
const MAX_POLLS     = 10;
let pollTimer       = null;

const poll = async () => {
    if (pollCount.value >= MAX_POLLS) {
        clearInterval(pollTimer);
        maxed.value = true;
        return;
    }
    try {
        const { data } = await axios.get(route('payment.status', props.transactionId));
        currentStatus.value = data.status;
        pollCount.value++;
        if (data.status === 'paid' || data.status === 'failed') {
            clearInterval(pollTimer);
        }
    } catch {
        clearInterval(pollTimer);
        maxed.value = true;
    }
};

onMounted(() => {
    if (currentStatus.value === 'pending') {
        pollTimer = setInterval(poll, 3000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const goToDashboard = () => router.visit(route('dashboard.subscriptions.index'));
const retryPoll     = () => { maxed.value = false; pollCount.value = 0; pollTimer = setInterval(poll, 3000); };
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-stone-800">Status Pembayaran</h1>
        </template>

        <div class="max-w-md mx-auto mt-8">
            <div class="rounded-2xl border p-8 text-center"
                 :class="{
                     'bg-emerald-50 border-emerald-100': currentStatus === 'paid',
                     'bg-white border-stone-100':        currentStatus === 'pending',
                     'bg-red-50 border-red-100':         currentStatus === 'failed',
                 }">

                <!-- Paid -->
                <template v-if="currentStatus === 'paid'">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-emerald-800 mb-2">Pembayaran Berhasil!</h2>
                    <p class="text-sm text-emerald-700 mb-6">Paket kamu sudah aktif. Selamat menikmati fitur Premium.</p>
                </template>

                <!-- Pending -->
                <template v-else-if="currentStatus === 'pending'">
                    <div class="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-4 animate-pulse">
                        <svg class="w-8 h-8 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-stone-800 mb-2">Menunggu Konfirmasi</h2>
                    <p class="text-sm text-stone-500 mb-4">Pembayaran sedang diproses. Halaman ini akan diperbarui otomatis.</p>
                    <div v-if="!maxed" class="flex items-center justify-center gap-2 text-xs text-stone-400 mb-6">
                        <span class="inline-block w-2 h-2 rounded-full bg-stone-300 animate-bounce"></span>
                        <span>Memeriksa status...</span>
                    </div>
                    <div v-else class="mb-6">
                        <p class="text-xs text-stone-400 mb-3">Cek otomatis selesai. Pembayaran mungkin masih diproses.</p>
                        <button @click="retryPoll"
                                class="px-4 py-2 rounded-xl text-sm font-semibold border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cek Ulang
                        </button>
                    </div>
                </template>

                <!-- Failed -->
                <template v-else>
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-red-800 mb-2">Pembayaran Gagal</h2>
                    <p class="text-sm text-red-600 mb-6">Terjadi masalah saat memproses pembayaran. Silakan coba lagi.</p>
                </template>

                <button @click="goToDashboard"
                        class="w-full py-3 rounded-xl text-sm font-semibold bg-brand-primary hover:bg-brand-primary-hover text-white transition-all">
                    Kembali ke Dashboard
                </button>
            </div>
        </div>
    </DashboardLayout>
</template>
```

- [ ] **Step 2: Verify build**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build completes without errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/PaymentReturn.vue
git commit -m "feat: add PaymentReturn.vue with status polling"
```

---

## Task 10: Run Full Test Suite + Verify

- [ ] **Step 1: Run all payment tests**

```bash
php artisan test tests/Feature/Payment/
```

Expected: All tests PASS (no failures).

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 3: Build frontend**

```bash
npm run build
```

Expected: No errors.

- [ ] **Step 4: Manual smoke test in browser**

1. Start dev server: `php artisan serve` + `npm run dev`
2. Login as test user
3. Navigate to `/dashboard/paket`
4. Click "Upgrade ke Premium" — should redirect to `pay.mayar.club/...` (sandbox)
5. Complete payment in Mayar sandbox
6. Verify redirect back to `/payment/return?txn=...`
7. Verify status polling works (status should update to `paid` after webhook fires)
8. Verify email sent (check `storage/logs/laravel.log` if using log driver)

- [ ] **Step 5: Final commit**

```bash
git add .
git commit -m "chore: complete Mayar payment migration - all tests passing"
```
