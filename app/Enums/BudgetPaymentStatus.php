<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case DP     = 'dp';
    case Paid   = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum bayar',
            self::DP     => 'DP',
            self::Paid   => 'Lunas',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-stone-100 text-stone-600',
            self::DP     => 'bg-amber-100 text-amber-700',
            self::Paid   => 'bg-emerald-100 text-emerald-700',
        };
    }
}
