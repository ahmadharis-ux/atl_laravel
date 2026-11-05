<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StrongPin implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{6}$/', $value)) {
            $fail('PIN harus terdiri dari 6 angka.');

            return;
        }

        if ($this->isWeak($value)) {
            $fail('PIN terlalu mudah ditebak. Gunakan kombinasi 6 angka yang lebih unik.');
        }
    }

    private function isWeak(string $pin): bool
    {
        if (preg_match('/^(\d)\1{5}$/', $pin) === 1) {
            return true;
        }

        if ($this->isSequential($pin)) {
            return true;
        }

        if (preg_match('/^(\d{2})\1{2}$/', $pin) === 1
            || preg_match('/^(\d{3})\1$/', $pin) === 1
            || preg_match('/^(\d)\1(\d)\2(\d)\3$/', $pin) === 1) {
            return true;
        }

        return in_array($pin, [
            '123456',
            '654321',
            '112233',
            '121212',
            '123123',
            '147258',
            '159753',
            '987654',
            '696969',
        ], true);
    }

    private function isSequential(string $pin): bool
    {
        $ascending = true;
        $descending = true;

        for ($index = 1; $index < strlen($pin); $index++) {
            $difference = (int) $pin[$index] - (int) $pin[$index - 1];

            if ($difference !== 1) {
                $ascending = false;
            }

            if ($difference !== -1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }
}
