<?php

// app/Enums/TemplateTier.php

declare(strict_types=1);

namespace App\Enums;

enum TemplateTier: string
{
    case Free    = 'free';
    case Premium = 'premium';

    public function label(): string
    {
        return match($this) {
            self::Free    => 'Gratis',
            self::Premium => 'Premium',
        };
    }

    public function isPremium(): bool
    {
        return $this === self::Premium;
    }
}
