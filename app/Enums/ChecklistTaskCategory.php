<?php

// app/Enums/ChecklistTaskCategory.php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTaskCategory: string
{
    case Administrasi = 'administrasi';
    case Venue        = 'venue';
    case Vendor       = 'vendor';
    case Busana       = 'busana';
    case Undangan     = 'undangan';
    case Tamu         = 'tamu';
    case Acara        = 'acara';
    case Dokumentasi  = 'dokumentasi';
    case Lainnya      = 'lainnya';

    public function label(): string
    {
        return match($this) {
            self::Administrasi => 'Administrasi',
            self::Venue        => 'Venue',
            self::Vendor       => 'Vendor',
            self::Busana       => 'Busana',
            self::Undangan     => 'Undangan',
            self::Tamu         => 'Tamu',
            self::Acara        => 'Acara',
            self::Dokumentasi  => 'Dokumentasi',
            self::Lainnya      => 'Lainnya',
        };
    }
}
