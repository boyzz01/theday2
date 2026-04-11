<?php

// app/Services/PhoneNumberNormalizer.php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class PhoneNumberNormalizer
{
    /**
     * Normalize an Indonesian phone number to 628xxxxxxxxxx format.
     *
     * @throws InvalidArgumentException when number cannot be normalized
     */
    public function normalize(string $phone): string
    {
        // Strip all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (empty($digits)) {
            throw new InvalidArgumentException("Nomor telepon tidak valid: {$phone}");
        }

        // Convert leading 0 → 62
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        // Strip leading + if present before digit (already handled above)
        // Handle case where someone passes "628xxx" already
        if (! str_starts_with($digits, '62')) {
            throw new InvalidArgumentException("Nomor telepon tidak valid: {$phone}");
        }

        // Validate length: 628 + 7-12 digits = 10-15 total
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            throw new InvalidArgumentException("Nomor telepon tidak valid: {$phone}");
        }

        return $digits;
    }

    public function isValid(string $phone): bool
    {
        try {
            $this->normalize($phone);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Format for display: 0812-3456-7890
     */
    public function formatDisplay(string $normalizedPhone): string
    {
        $local = '0' . substr($normalizedPhone, 2);

        if (strlen($local) >= 10) {
            return substr($local, 0, 4) . '-' . substr($local, 4, 4) . '-' . substr($local, 8);
        }

        return $local;
    }
}
