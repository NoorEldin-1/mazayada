<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seeds the editable platform parameters (spec §8.2). Idempotent — only the
 * row's metadata (type/group) is kept in sync; an admin-edited value is never
 * overwritten on re-seed (updateOrCreate matches on key, sets value only when
 * the row is first created via the value in the second arg of firstOrCreate).
 */
class SystemSettingsSeeder extends Seeder
{
    /** key => [value, type, group] — defaults mirror config/mazayada.php. */
    private const DEFAULTS = [
        'bidding.extension_trigger_seconds' => ['30', 'int', 'bidding'],
        'bidding.extension_duration_minutes' => ['5', 'int', 'bidding'],
        'bidding.max_per_minute' => ['10', 'int', 'bidding'],

        'kyc.pending_grace_days' => ['30', 'int', 'kyc'],
        'kyc.doc_max_kb' => ['1024', 'int', 'kyc'],
        'kyc.biometric_max_kb' => ['120', 'int', 'kyc'],

        'security.login_max_attempts' => ['5', 'int', 'security'],
        'security.login_decay_minutes' => ['15', 'int', 'security'],
        'security.enforce_admin_2fa' => ['0', 'bool', 'security'],

        'identity.nin_checksum_enforced' => ['0', 'bool', 'identity'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => [$value, $type, $group]) {
            // firstOrCreate so an admin-edited value survives re-seeding; only
            // the metadata is refreshed for existing rows.
            $setting = SystemSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group],
            );

            if ($setting->wasRecentlyCreated === false) {
                $setting->update(['type' => $type, 'group' => $group]);
            }
        }

        Cache::forget('system_settings');
    }
}
