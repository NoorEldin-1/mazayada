<?php

namespace Tests\Unit;

use App\Rules\AlgerianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AlgerianPhoneTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_it_accepts_valid_algerian_phones(string $phone): void
    {
        $failures = [];
        (new AlgerianPhone)->validate('phone', $phone, function ($message) use (&$failures) {
            $failures[] = $message;
        });
        $this->assertSame([], $failures, 'Phone '.$phone.' should be valid');
    }

    public static function validPhones(): array
    {
        return [
            'mobilis_05' => ['0555123456'],
            'djezzy_06' => ['0661234567'],
            'ooredoo_07' => ['0791234567'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_it_rejects_invalid_phones(string $phone): void
    {
        $failures = [];
        (new AlgerianPhone)->validate('phone', $phone, function ($message) use (&$failures) {
            $failures[] = $message;
        });
        $this->assertNotEmpty($failures, 'Phone '.$phone.' should be invalid');
    }

    public static function invalidPhones(): array
    {
        return [
            'starts_with_04' => ['0455123456'],
            'starts_with_08' => ['0855123456'],
            'starts_with_1' => ['1555123456'],
            'too_short' => ['055512345'],
            'too_long' => ['05551234567'],
            'contains_letters' => ['0555ABC456'],
            'empty' => [''],
        ];
    }
}
