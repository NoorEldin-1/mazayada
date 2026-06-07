<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Algerian Tax Identification Number (NIF) — 15 digits for natural persons
 * (spec §3.2). Required for merchants/craftsmen; optional otherwise.
 */
class NifValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{15}$/', (string) $value)) {
            $fail(__('rules.nif_invalid'));
        }
    }
}
