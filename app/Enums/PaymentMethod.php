<?php

// app/Enums/PaymentMethod.php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Mayar = 'mayar';

    public function label(): string
    {
        return 'Mayar';
    }
}
