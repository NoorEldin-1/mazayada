<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Algerian National Identification Number (NIN), per spec §3.1.
 *
 * The 18-digit format is ALWAYS enforced. The control-key (checksum) check is
 * a best-effort, reverse-engineered algorithm — the spec itself warns it is
 * unconfirmed and recommends falling back to a format-only check. So it is
 * OFF by default and only runs when 'identity.nin_checksum_enforced' is enabled
 * (system setting, falling back to config/mazayada.php). This prevents wrongly
 * rejecting citizens who hold genuinely valid numbers.
 */
class NinValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{18}$/', $value)) {
            $fail(__('rules.nin_length'));

            return;
        }

        // Soft by default — only validate the control key when explicitly enabled.
        if (! setting('identity.nin_checksum_enforced', false)) {
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
            $fail(__('rules.nin_invalid'));
        }
    }
}
