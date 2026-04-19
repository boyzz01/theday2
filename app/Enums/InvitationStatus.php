<?php

// app/Enums/InvitationStatus.php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Published => 'Dipublikasikan',
            self::Archived  => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Published => 'green',
            self::Archived  => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Draft || $this === self::Published;
    }
}
