<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NinValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d{18}$/', $value)) {
            $fail('رقم التعريف الوطني يجب أن يكون 18 رقماً.');
            return;
        }

        $weights = [2, 3, 4, 5, 6, 7];
        $sum = 0;
        $digits = str_split(substr($value, 0, 16));

        for ($i = 15; $i >= 0; $i--) {
            $sum += (int) $digits[$i] * $weights[(15 - $i) % 6];
        }

        $checksum = 97 - ($sum % 97);
        $expected = (int) substr($value, 16, 2);

        if ($checksum !== $expected) {
            $fail('رقم التعريف الوطني غير صالح.');
        }
    }
}
