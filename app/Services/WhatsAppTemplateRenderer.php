<?php

// app/Services/WhatsAppTemplateRenderer.php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestList;
use App\Models\Invitation;

class WhatsAppTemplateRenderer
{
    private const KNOWN_PLACEHOLDERS = [
        'guest_name',
        'invitation_url',
        'greeting',
        'bride_name',
        'groom_name',
        'event_date',
        'event_time',
        'event_location',
        'custom_slug',
    ];

    public function __construct(
        private readonly PersonalInvitationUrlBuilder $urlBuilder
    ) {}

    /**
     * Render template for actual sending (final render).
     * Unknown placeholders are removed.
     */
    public function render(string $template, GuestList $guest, ?Invitation $invitation = null): string
    {
        $context = $this->buildContext($guest, $invitation);

        return $this->replace($template, $context, removeUnknown: true);
    }

    /**
     * Preview render with sample data (for template editor).
     * Unknown placeholders are kept as-is for visibility.
     */
    public function preview(string $template, ?GuestList $guest = null, ?Invitation $invitation = null): array
    {
        $context  = $guest
            ? $this->buildContext($guest, $invitation)
            : $this->sampleContext();

        $rendered = $this->replace($template, $context, removeUnknown: false);
        $warnings = $this->detectUnknownPlaceholders($template, $context);

        return [
            'rendered' => $rendered,
            'warnings' => $warnings,
        ];
    }

    /**
     * Extract placeholder keys from a template string.
     *
     * @return string[]
     */
    public function extractPlaceholders(string $template): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $template, $matches);

        return array_unique($matches[1]);
    }

    // ─── Private ──────────────────────────────────────────────────

    private function buildContext(GuestList $guest, ?Invitation $invitation): array
    {
        $url     = $invitation
            ? $this->urlBuilder->buildForGuest($invitation, $guest)
            : '';

        $details = $invitation?->details;
        $event   = $invitation?->events?->first();

        return [
            'guest_name'     => $guest->name,
            'invitation_url' => $url,
            'greeting'       => $guest->greeting ?? '',
            'bride_name'     => $details?->bride_name ?? '',
            'groom_name'     => $details?->groom_name ?? '',
            'event_date'     => $event?->event_date?->translatedFormat('d MMMM Y') ?? '',
            'event_time'     => $event?->start_time ?? '',
            'event_location' => $event?->venue_name ?? '',
            'custom_slug'    => $invitation?->slug ?? '',
        ];
    }

    private function sampleContext(): array
    {
        return [
            'guest_name'     => 'Bapak Andi',
            'invitation_url' => url('/nama-undangan/bapak-andi'),
            'greeting'       => 'Bapak',
            'bride_name'     => 'Novi',
            'groom_name'     => 'Ardi',
            'event_date'     => '10 Mei 2026',
            'event_time'     => '10:00',
            'event_location' => 'The Grand Ballroom',
            'custom_slug'    => 'nama-undangan',
        ];
    }

    private function replace(string $template, array $context, bool $removeUnknown): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($context, $removeUnknown) {
            $key = $matches[1];

            if (array_key_exists($key, $context)) {
                return $context[$key];
            }

            return $removeUnknown ? '' : $matches[0];
        }, $template);
    }

    private function detectUnknownPlaceholders(string $template, array $context): array
    {
        $found   = $this->extractPlaceholders($template);
        $unknown = array_filter($found, fn ($key) => ! array_key_exists($key, $context));

        return array_values(array_map(
            fn ($key) => "Placeholder {{{{$key}}}} tidak dikenali.",
            $unknown
        ));
    }
}
