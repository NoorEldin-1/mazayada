<?php

namespace Tests\Unit;

use App\Rules\NinValidation;
use Illuminate\Support\Facades\Cache;
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

    public function test_it_rejects_invalid_checksum_when_enforced(): void
    {
        // Checksum validation is OFF by default (spec §3.1 — the reverse-engineered
        // algorithm is unconfirmed). Turn it on explicitly to test the branch.
        $this->enforceChecksum();

        // 18 digits but the checksum (last 2) is intentionally wrong.
        $failures = [];
        $this->rule->validate('nin', '109823041175663899', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertNotEmpty($failures);
    }

    public function test_it_accepts_invalid_checksum_when_soft_default(): void
    {
        // With the default (soft) setting only the 18-digit format is enforced,
        // so a wrong control key is accepted — citizens with genuinely valid
        // numbers are never wrongly blocked.
        Cache::forget('system_settings');

        $failures = [];
        $this->rule->validate('nin', '109823041175663899', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertSame([], $failures);
    }

    public function test_it_accepts_a_known_valid_nin_when_enforced(): void
    {
        $this->enforceChecksum();

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

    private function enforceChecksum(): void
    {
        config(['mazayada.identity.nin_checksum_enforced' => true]);
        Cache::forget('system_settings');
    }
}
