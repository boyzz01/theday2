<?php

// app/Enums/GuestSendStatus.php

declare(strict_types=1);

namespace App\Enums;

enum GuestSendStatus: string
{
    case NotSent = 'not_sent';
    case Sent    = 'sent';
    case Opened  = 'opened';

    public function label(): string
    {
        return match($this) {
            self::NotSent => 'Belum Dikirim',
            self::Sent    => 'Sudah Dikirim',
            self::Opened  => 'Sudah Dibuka',
        };
    }
}
