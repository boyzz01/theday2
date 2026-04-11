<?php

// app/Enums/ChecklistTaskPriority.php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTaskPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';

    public function label(): string
    {
        return match($this) {
            self::Low    => 'Rendah',
            self::Medium => 'Sedang',
            self::High   => 'Tinggi',
        };
    }
}
