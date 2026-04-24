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
}
