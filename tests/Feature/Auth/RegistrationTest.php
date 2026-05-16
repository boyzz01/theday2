<?php

namespace Tests\Feature\Auth;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name'               => 'Free',
            'slug'               => 'free',
            'price'              => 0,
            'duration_days'      => 0,
            'max_invitations'    => 3,
            'max_gallery_photos' => 10,
            'custom_music'       => false,
            'remove_watermark'   => false,
            'custom_domain'      => false,
            'analytics_access'   => false,
            'is_active'          => true,
            'sort_order'         => 0,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'phone'                 => '081234567890',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
