<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InertiaTranslationsShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a dummy Inertia route for testing share().
        // Path starts with /dashboard so it is excluded from the wildcard
        // /{slug} route (which carries the `invitation.access` middleware).
        Route::get('/dashboard/__inertia_share_test', function () {
            return inertia('Dashboard/Index');
        })->middleware(['web']);
    }

    public function test_shares_translations_from_id_json_when_locale_is_id(): void
    {
        $response = $this->withHeader('X-Locale', 'id')->get('/dashboard/__inertia_share_test');

        $response->assertOk();

        $props = $response->inertiaProps();

        $this->assertSame('id', $props['locale']);
        $this->assertIsArray($props['translations']);
        $this->assertSame('Simpan', $props['translations']['common']['save'] ?? null);
    }

    public function test_shares_translations_from_en_json_when_locale_is_en(): void
    {
        $response = $this->withHeader('X-Locale', 'en')->get('/dashboard/__inertia_share_test');

        $response->assertOk();

        $props = $response->inertiaProps();

        $this->assertSame('en', $props['locale']);
        $this->assertIsArray($props['translations']);
        $this->assertSame('Save', $props['translations']['common']['save'] ?? null);
    }

    public function test_falls_back_to_config_app_locale_when_no_header_or_cookie(): void
    {
        config(['app.locale' => 'id']);

        $response = $this->get('/dashboard/__inertia_share_test');

        $response->assertOk();

        $props = $response->inertiaProps();

        $this->assertSame('id', $props['locale']);
    }
}
