<?php

namespace App\Support;

/**
 * Phone numbers are stored in international format without "+" or spaces ("355691234567"),
 * which is what WhatsApp Cloud API and wa.me links expect.
 */
class Phones
{
    /** Normalises what people type: "069 123 4567", "+355 69 123 4567", "00355691234567". */
    public static function normalize(?string $raw, ?string $defaultCountryCode = null): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $defaultCountryCode ??= config('product.default_country_code', '355');
        $trimmed = trim($raw);
        $plus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D/', '', $trimmed);
        if ($digits === '') {
            return null;
        }
        if ($plus) {
            return $digits;
        }
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return $defaultCountryCode.substr($digits, 1);
        }
        if (strlen($digits) <= 9) {
            return $defaultCountryCode.$digits;
        }

        return $digits;
    }

    public static function isPlausible(?string $normalized): bool
    {
        return $normalized !== null && strlen($normalized) >= 8 && strlen($normalized) <= 15;
    }
}
