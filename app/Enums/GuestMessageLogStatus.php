<?php

// app/Enums/GuestMessageLogStatus.php

declare(strict_types=1);

namespace App\Enums;

enum GuestMessageLogStatus: string
{
    case ConfirmedSent = 'confirmed_sent';
    case Copied        = 'copied';
    case Cancelled     = 'cancelled';
}
