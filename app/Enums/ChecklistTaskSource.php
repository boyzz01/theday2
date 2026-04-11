<?php

// app/Enums/ChecklistTaskSource.php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTaskSource: string
{
    case System = 'system';
    case User   = 'user';
}
