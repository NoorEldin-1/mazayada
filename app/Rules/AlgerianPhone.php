<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlgerianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^0[567]\d{8}$/', $value)) {
            $fail('رقم الهاتف يجب أن يكون 10 أرقام ويبدأ بـ 05 أو 06 أو 07.');
        }
    }
}
