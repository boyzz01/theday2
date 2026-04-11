<?php

// app/Services/GuestSlugGenerator.php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestList;
use Illuminate\Support\Str;

class GuestSlugGenerator
{
    /**
     * Generate a unique guest slug within the scope of an invitation.
     *
     * @param string      $name         Guest's name
     * @param string|null $invitationId Invitation UUID (null = no invitation)
     * @param string|null $userId       User UUID (used when invitationId is null)
     * @param int|null    $excludeId    GuestList ID to exclude from uniqueness check (for updates)
     */
    public function generate(
        string $name,
        ?string $invitationId,
        ?string $userId = null,
        ?int $excludeId = null
    ): string {
        $base = Str::slug($name);

        if (empty($base)) {
            $base = 'tamu';
        }

        $slug    = $base;
        $counter = 2;

        while ($this->slugExists($slug, $invitationId, $userId, $excludeId)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(
        string $slug,
        ?string $invitationId,
        ?string $userId,
        ?int $excludeId
    ): bool {
        $query = GuestList::where('guest_slug', $slug);

        if ($invitationId !== null) {
            $query->where('invitation_id', $invitationId);
        } else {
            $query->whereNull('invitation_id');
            if ($userId !== null) {
                $query->where('user_id', $userId);
            }
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
