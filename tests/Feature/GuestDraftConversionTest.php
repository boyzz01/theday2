<?php

// tests/Feature/GuestDraftConversionTest.php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\ConvertGuestDraftAction;
use App\Models\GuestDraft;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestDraftConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AssignFreeSubscriptionAction requires a Free plan to exist.
        Plan::create([
            'name'               => 'Free',
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

    // ── ConvertGuestDraftAction unit-style tests ──────────────────────────────

    public function test_converts_guest_draft_to_invitation(): void
    {
        $user      = User::factory()->create();
        $template  = Template::factory()->create();
        $sessionId = (string) Str::uuid();

        GuestDraft::create([
            'guest_session_id' => $sessionId,
            'template_id'      => $template->id,
            'step'             => 2,
            'data'             => [
                'title'      => 'Pernikahan Rina & Budi',
                'event_type' => 'pernikahan',
                'details'    => [
                    'groom_name' => 'Budi',
                    'bride_name' => 'Rina',
                ],
                'events' => [
                    [
                        'event_name'   => 'Akad Nikah',
                        'event_date'   => '2026-07-12',
                        'start_time'   => '09:00',
                        'venue_name'   => 'Masjid Al-Ikhlas',
                        'venue_address'=> 'Jakarta Selatan',
                    ],
                ],
            ],
            'expires_at' => now()->addDays(7),
        ]);

        $action     = app(ConvertGuestDraftAction::class);
        $invitation = $action->execute($user, $sessionId);

        $this->assertNotNull($invitation);
        $this->assertInstanceOf(Invitation::class, $invitation);
        $this->assertSame($user->id, $invitation->user_id);
        $this->assertSame($template->id, $invitation->template_id);
        $this->assertSame('Pernikahan Rina & Budi', $invitation->title);
        $this->assertSame('draft', $invitation->status->value);

        // Details created
        $this->assertNotNull($invitation->details);
        $this->assertSame('Budi', $invitation->details->groom_name);
        $this->assertSame('Rina', $invitation->details->bride_name);

        // Events created
        $this->assertCount(1, $invitation->events);
        $this->assertSame('Akad Nikah', $invitation->events->first()->event_name);

        // Draft deleted after conversion
        $this->assertDatabaseMissing('guest_drafts', ['guest_session_id' => $sessionId]);
    }

    public function test_returns_null_when_no_draft_found(): void
    {
        $user   = User::factory()->create();
        $action = app(ConvertGuestDraftAction::class);

        $result = $action->execute($user, (string) Str::uuid());

        $this->assertNull($result);
    }

    public function test_returns_null_for_expired_draft(): void
    {
        $user      = User::factory()->create();
        $sessionId = (string) Str::uuid();

        GuestDraft::create([
            'guest_session_id' => $sessionId,
            'data'             => ['title' => 'Test'],
            'expires_at'       => now()->subHour(),
        ]);

        $action = app(ConvertGuestDraftAction::class);
        $result = $action->execute($user, $sessionId);

        $this->assertNull($result);
        // Expired draft NOT deleted (only valid drafts are converted)
        $this->assertDatabaseHas('guest_drafts', ['guest_session_id' => $sessionId]);
    }

    public function test_generates_unique_slug(): void
    {
        $user      = User::factory()->create();
        $template  = Template::factory()->create();
        $sessionId = (string) Str::uuid();

        GuestDraft::create([
            'guest_session_id' => $sessionId,
            'template_id'      => $template->id,
            'data'             => ['title' => 'Undangan Spesial'],
            'expires_at'       => now()->addDays(7),
        ]);

        $action     = app(ConvertGuestDraftAction::class);
        $invitation = $action->execute($user, $sessionId);

        $this->assertNotNull($invitation);
        $this->assertStringStartsWith('undangan-spesial-', $invitation->slug);
    }

    // ── Integration: register flow ────────────────────────────────────────────

    public function test_registration_converts_guest_draft_and_redirects_to_dashboard(): void
    {
        $template  = Template::factory()->create();
        $sessionId = (string) Str::uuid();

        GuestDraft::create([
            'guest_session_id' => $sessionId,
            'template_id'      => $template->id,
            'data'             => [
                'title'      => 'Undangan Pernikahan',
                'event_type' => 'pernikahan',
            ],
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->withCookie('guest_session_id', $sessionId)
            ->post('/register', [
                'name'                  => 'Test User',
                'phone'                 => '081234567890',
                'email'                 => 'test@example.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // Draft converted → invitation exists
        $this->assertDatabaseHas('invitations', ['title' => 'Undangan Pernikahan']);
        // Draft deleted
        $this->assertDatabaseMissing('guest_drafts', ['guest_session_id' => $sessionId]);
    }

    public function test_registration_without_guest_draft_redirects_to_dashboard(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'phone'                 => '081234567890',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseCount('invitations', 0);
    }

    // ── Integration: login flow ───────────────────────────────────────────────

    public function test_login_converts_guest_draft(): void
    {
        $user      = User::factory()->create();
        $template  = Template::factory()->create();
        $sessionId = (string) Str::uuid();

        GuestDraft::create([
            'guest_session_id' => $sessionId,
            'template_id'      => $template->id,
            'data'             => [
                'title'      => 'Undangan Login',
                'event_type' => 'pernikahan',
            ],
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->withCookie('guest_session_id', $sessionId)
            ->post('/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('invitations', ['title' => 'Undangan Login', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('guest_drafts', ['guest_session_id' => $sessionId]);
    }

    // ── API: email check ──────────────────────────────────────────────────────

    public function test_email_check_returns_available_for_new_email(): void
    {
        $response = $this->postJson('/api/auth/check-email', [
            'email' => 'baru@example.com',
        ]);

        $response->assertOk()->assertJson(['available' => true]);
    }

    public function test_email_check_returns_unavailable_for_existing_email(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->postJson('/api/auth/check-email', [
            'email' => 'ada@example.com',
        ]);

        $response->assertOk()->assertJson(['available' => false]);
    }
}
