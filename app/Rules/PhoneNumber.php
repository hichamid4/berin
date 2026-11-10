<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a phone number stored in E.164 format (for example +212612345678).
 *
 * Moroccan numbers (+212) must have exactly nine national digits starting with 5, 6 or 7.
 * Other countries follow the generic E.164 length of 8 to 15 digits.
 */
class PhoneNumber implements ValidationRule
{
    public const MESSAGE = 'Enter a valid phone number in international format, for example +212612345678 or 0612345678.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail(self::MESSAGE);
        }
    }

    public static function isValid(string $value): bool
    {
        if (str_starts_with($value, '+212')) {
            return preg_match('/^\+212[5-7]\d{8}$/', $value) === 1;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $value) === 1;
    }

    /**
     * Convert common user input to E.164: removes spaces, dots, dashes and brackets,
     * turns a leading 00 into +, and turns a Moroccan local number (06…, 07…, 05…) into +212….
     */
    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/[\s.\-()]/', '', trim($value));

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        if (preg_match('/^0[5-7]\d{8}$/', $digits) === 1) {
            return '+212'.substr($digits, 1);
        }

        if (preg_match('/^212[5-7]\d{8}$/', $digits) === 1) {
            return '+'.$digits;
        }

        return $digits;
    }
}
