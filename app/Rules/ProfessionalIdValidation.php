<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Algerian "professional identification number" (الرقم التعريف المهني) for
 * entity staff.
 *
 * Unlike the NIN (18 digits) there is no single national professional number
 * with a fixed checksum — the closest fiscal/statistical identifiers vary in
 * length (NIF 15, NIS 9/18). So this is a deliberately lenient, format-only
 * check: digits only, 6–20 long, with spaces and dashes ignored.
 */
class ProfessionalIdValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = preg_replace('/[\s-]+/', '', (string) $value);

        if (! preg_match('/^\d{6,20}$/', (string) $normalized)) {
            $fail(__('rules.professional_id_invalid'));
        }
    }
}
