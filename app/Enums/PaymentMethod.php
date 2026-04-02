<?php

// app/Enums/PaymentMethod.php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Midtrans = 'midtrans';
    case Xendit   = 'xendit';

    public function label(): string
    {
        return match($this) {
            self::Midtrans => 'Midtrans',
            self::Xendit   => 'Xendit',
        };
    }
}
