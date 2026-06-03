<?php

namespace Tests\Unit;

use App\Rules\NinValidation;
use Tests\TestCase;

class NinValidationTest extends TestCase
{
    private NinValidation $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NinValidation;
    }

    public function test_it_rejects_non_18_digit_input(): void
    {
        $failures = [];
        $this->rule->validate('nin', '12345', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_rejects_non_numeric_input(): void
    {
        $failures = [];
        $this->rule->validate('nin', 'ABC123456789012345', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_rejects_empty_input(): void
    {
        $failures = [];
        $this->rule->validate('nin', '', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_rejects_19_digits(): void
    {
        $failures = [];
        $this->rule->validate('nin', '1234567890123456789', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_rejects_invalid_checksum(): void
    {
        // 18 digits but the checksum (last 2) is intentionally wrong.
        $failures = [];
        $this->rule->validate('nin', '109823041175663899', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_accepts_a_known_valid_nin(): void
    {
        // Build a valid NIN using the same algorithm the rule uses
        // so the test stays correct if the algorithm constants change.
        $base = '1098230411756638';
        $weights = [2, 3, 4, 5, 6, 7];
        $digits = str_split($base);
        $sum = 0;
        for ($i = 15; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weights[(15 - $i) % 6];
        }
        $checksum = str_pad((string) (97 - ($sum % 97)), 2, '0', STR_PAD_LEFT);
        $valid = $base.$checksum;

        $failures = [];
        $this->rule->validate('nin', $valid, function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertSame([], $failures, 'Expected NIN '.$valid.' to pass validation. Got: '.json_encode($failures));
    }
}
