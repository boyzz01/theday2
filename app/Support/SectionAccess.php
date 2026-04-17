<?php

// app/Support/SectionAccess.php
// Centralized helpers for plan-based section access control.
// Use these instead of scattered inline isPremium() checks.

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

class SectionAccess
{
    /** True when the user has an active Premium subscription. */
    public static function isPremium(User $user): bool
    {
        return $user->isPremium();
    }

    /** True when the user is allowed to edit this section. */
    public static function canEditSection(User $user, string $sectionKey): bool
    {
        if (static::isPremium($user)) {
            return true;
        }

        return (bool) config("sections.{$sectionKey}.editable_by_free", false);
    }

    /** Maximum gallery photos allowed for this user. */
    public static function maxGalleryPhotos(User $user): int
    {
        if (static::isPremium($user)) {
            return 9999;
        }

        return (int) config('plans.free.max_photos', 5);
    }

    /** True when the user can upload their own audio file. */
    public static function canUseCustomMusic(User $user): bool
    {
        return static::isPremium($user);
    }

    /** True when the user can use premium templates. */
    public static function canUsePremiumTemplate(User $user): bool
    {
        return static::isPremium($user);
    }

    /** Section keys that are premium-only. */
    public static function premiumSectionKeys(): array
    {
        return array_keys(array_filter(
            config('sections', []),
            fn (array $cfg) => ($cfg['tier'] ?? 'free') === 'premium',
        ));
    }
}
