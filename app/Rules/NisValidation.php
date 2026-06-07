<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Algerian Statistical Identification Number (NIS) — 9 digits (base) or 18
 * digits (with branch suffix), per spec §3.2. For registered companies.
 */
class NisValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(\d{9}|\d{18})$/', (string) $value)) {
            $fail(__('rules.nis_invalid'));
        }
    }
}
