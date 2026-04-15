<?php

// app/Enums/ChecklistTaskCategory.php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTaskCategory: string
{
    case Administrasi = 'administrasi';
    case Venue        = 'venue';
    case Vendor       = 'vendor';
    case Undangan     = 'undangan';
    case Keuangan     = 'keuangan';
    case Busana       = 'busana';
    case Dekorasi     = 'dekorasi';
    case Dokumentasi  = 'dokumentasi';
    case Tamu         = 'tamu';
    case Acara        = 'acara';
    case Lainnya      = 'lainnya';

    public function label(): string
    {
        return match($this) {
            self::Administrasi => 'Administrasi',
            self::Venue        => 'Venue',
            self::Vendor       => 'Vendor',
            self::Undangan     => 'Undangan',
            self::Keuangan     => 'Keuangan',
            self::Busana       => 'Busana',
            self::Dekorasi     => 'Dekorasi',
            self::Dokumentasi  => 'Dokumentasi',
            self::Tamu         => 'Tamu',
            self::Acara        => 'Acara',
            self::Lainnya      => 'Lainnya',
        };
    }
}
