<?php

declare(strict_types=1);

namespace App\Modules\User\Service\Phone;

final class PhoneNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null || !preg_match('/^\+?[0-9 ().-]{10,30}$/D', trim($value))) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $value);
        if (preg_match('/^9[0-9]{9}$/D', $digits)) {
            return '7' . $digits;
        }

        return preg_match('/^[78]9[0-9]{9}$/D', $digits) ? '7' . substr($digits, 1) : null;
    }
}
