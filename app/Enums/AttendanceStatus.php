<?php

// app/Enums/AttendanceStatus.php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Hadir      = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Ragu       = 'ragu';

    public function label(): string
    {
        return match($this) {
            self::Hadir      => 'Hadir',
            self::TidakHadir => 'Tidak Hadir',
            self::Ragu       => 'Masih Ragu',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Hadir      => 'green',
            self::TidakHadir => 'red',
            self::Ragu       => 'yellow',
        };
    }
}
