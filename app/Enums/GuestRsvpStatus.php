<?php

// app/Enums/GuestRsvpStatus.php

declare(strict_types=1);

namespace App\Enums;

enum GuestRsvpStatus: string
{
    case Pending      = 'pending';
    case Attending    = 'attending';
    case NotAttending = 'not_attending';

    public function label(): string
    {
        return match($this) {
            self::Pending      => 'Belum Respon',
            self::Attending    => 'Hadir',
            self::NotAttending => 'Tidak Hadir',
        };
    }
}
