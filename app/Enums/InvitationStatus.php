<?php

// app/Enums/InvitationStatus.php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Expired   = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Published => 'Dipublikasikan',
            self::Expired   => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Published => 'green',
            self::Expired   => 'red',
        };
    }
}
