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
        $this->user = User::factory()->create(['onboarding_completed_at' => now()]);
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
        $freeUser = User::factory()->create(['onboarding_completed_at' => now()]);

        $response = $this->actingAs($freeUser)
            ->postJson(route('dashboard.addons.checkout'), ['quantity' => 1]);

        $response->assertStatus(403);
    }
}
