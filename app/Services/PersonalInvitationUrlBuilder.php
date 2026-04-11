<?php

// app/Services/PersonalInvitationUrlBuilder.php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestList;
use App\Models\Invitation;

class PersonalInvitationUrlBuilder
{
    /**
     * Build the personal invitation URL for a specific guest.
     * Format: https://theday.id/{invitationSlug}/{guestSlug}
     */
    public function buildForGuest(Invitation $invitation, GuestList $guest): string
    {
        return url("/{$invitation->slug}/{$guest->guest_slug}");
    }

    /**
     * Build the public (non-personalized) invitation URL.
     */
    public function buildPublic(Invitation $invitation): string
    {
        return url("/{$invitation->slug}");
    }

    /**
     * Build URL from raw slugs (used in tracker without full models).
     */
    public function buildFromSlugs(string $invitationSlug, string $guestSlug): string
    {
        return url("/{$invitationSlug}/{$guestSlug}");
    }
}
