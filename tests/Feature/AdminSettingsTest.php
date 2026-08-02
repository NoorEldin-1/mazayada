<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for the System Settings module (spec §8.2), which was
 * entirely unusable: saving crashed with a 500 and, once that was fixed, every
 * boolean parameter was silently written back as "off".
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);

        $this->admin = User::create([
            'nin' => str_pad((string) random_int(0, 999999999999999999), 18, '1', STR_PAD_LEFT),
            'first_name_ar' => 'مدير',
            'last_name_ar' => 'النظام',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'settings-admin'.uniqid().'@mazayada.test',
            'birth_date' => '1985-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN,
            'email_verified' => true,
        ]);

        $this->admin->syncRoles([UserRole::SUPER_ADMIN->value]);
    }

    /**
     * The crash: AuditLog::log() declared a non-nullable string $resourceId and
     * the settings save — a platform-wide action with no resource row — passed
     * null, raising a TypeError that surfaced as a bare 500 page.
     */
    public function test_saving_settings_succeeds_and_is_audited(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => ['bidding.max_per_minute' => '7'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('7', SystemSetting::find('bidding.max_per_minute')->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SETTINGS_UPDATED']);
    }

    /**
     * Setting keys contain dots, so reading the payload through Laravel's dot
     * notation resolved them as nesting and returned false for every checkbox —
     * turning on a boolean parameter never persisted.
     */
    public function test_boolean_settings_with_dotted_keys_persist(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => ['identity.nin_checksum_enforced' => '1'],
            ])
            ->assertRedirect();

        $this->assertSame('1', SystemSetting::find('identity.nin_checksum_enforced')->value);
    }

    public function test_unchecked_boolean_settings_are_stored_as_off(): void
    {
        SystemSetting::query()->whereKey('identity.nin_checksum_enforced')->update(['value' => '1']);

        // An unchecked checkbox is simply absent from the payload.
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), ['settings' => []])
            ->assertRedirect();

        $this->assertSame('0', SystemSetting::find('identity.nin_checksum_enforced')->value);
    }

    public function test_non_numeric_value_is_rejected_instead_of_cast_to_zero(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => ['bidding.max_per_minute' => 'not-a-number'],
            ])
            ->assertSessionHasErrors();

        $this->assertSame('10', SystemSetting::find('bidding.max_per_minute')->value);
    }
}
