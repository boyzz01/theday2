<?php

// app/Enums/EventType.php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Pernikahan = 'pernikahan';
    case UlangTahun = 'ulang_tahun';

    public function label(): string
    {
        return match($this) {
            self::Pernikahan  => 'Pernikahan',
            self::UlangTahun  => 'Ulang Tahun',
        };
    }
}
