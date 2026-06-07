<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Algérie Poste account number (RIP — Relevé d'Identité Postale), 20 digits
 * (spec §3.3). Spaces are tolerated and stripped before checking.
 */
class RipValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = preg_replace('/\s+/', '', (string) $value);

        if (! preg_match('/^\d{20}$/', $normalized)) {
            $fail(__('rules.rip_invalid'));
        }
    }
}
