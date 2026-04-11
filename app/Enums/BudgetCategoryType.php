<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetCategoryType: string
{
    case System = 'system';
    case Custom = 'custom';
}
