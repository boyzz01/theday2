<?php

// app/Enums/PaymentMethod.php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Mayar    = 'mayar';
    case Midtrans = 'midtrans'; // keep for historical records
    case Xendit   = 'xendit';

    public function label(): string
    {
        return match($this) {
            self::Mayar    => 'Mayar',
            self::Midtrans => 'Midtrans',
            self::Xendit   => 'Xendit',
        };
    }
}
