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
        $user        = User::factory()->create(['onboarding_completed_at' => now()]);
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
        $owner       = User::factory()->create(['onboarding_completed_at' => now()]);
        $otherUser   = User::factory()->create(['onboarding_completed_at' => now()]);
        $transaction = $this->makeTransaction($owner, PaymentStatus::Pending);

        $response = $this->actingAs($otherUser)
            ->get('/payment/return?txn=' . $transaction->id);

        $response->assertForbidden();
    }

    public function test_status_endpoint_returns_current_status(): void
    {
        $user        = User::factory()->create(['onboarding_completed_at' => now()]);
        $transaction = $this->makeTransaction($user, PaymentStatus::Pending);

        $response = $this->actingAs($user)
            ->getJson('/payment/transactions/' . $transaction->id . '/status');

        $response->assertOk()
            ->assertJsonPath('status', 'pending');
    }

    public function test_status_endpoint_returns_403_for_wrong_user(): void
    {
        $owner       = User::factory()->create(['onboarding_completed_at' => now()]);
        $otherUser   = User::factory()->create(['onboarding_completed_at' => now()]);
        $transaction = $this->makeTransaction($owner, PaymentStatus::Pending);

        $response = $this->actingAs($otherUser)
            ->getJson('/payment/transactions/' . $transaction->id . '/status');

        $response->assertForbidden();
    }
}
