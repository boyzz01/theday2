<?php

declare(strict_types=1);

namespace App\Support\Formatters;

final class RupiahFormatter
{
    public static function format(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function formatOrZero(?int $amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }
}
