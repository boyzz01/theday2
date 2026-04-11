<?php

// app/Enums/ChecklistTaskStatus.php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTaskStatus: string
{
    case Todo     = 'todo';
    case Done     = 'done';
    case Archived = 'archived';
}
