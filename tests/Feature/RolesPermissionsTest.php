<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_all_required_roles_exist_after_seeding(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertTrue(
                Role::where('name', $role->value)->exists(),
                "Role {$role->value} should exist after seeding."
            );
        }
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = Role::findByName(UserRole::SUPER_ADMIN->value);
        $totalPermissions = Permission::count();

        $this->assertGreaterThan(0, $totalPermissions);
        $this->assertSame($totalPermissions, $superAdmin->permissions()->count());
    }

    public function test_citizen_can_place_bids_and_view_auctions(): void
    {
        $citizen = Role::findByName(UserRole::CITIZEN->value);

        $this->assertTrue($citizen->hasPermissionTo('bids.place'));
        $this->assertTrue($citizen->hasPermissionTo('auctions.view'));
        $this->assertTrue($citizen->hasPermissionTo('auctions.viewAny'));
    }

    public function test_citizen_cannot_create_or_publish_auctions(): void
    {
        $citizen = Role::findByName(UserRole::CITIZEN->value);

        $this->assertFalse($citizen->hasPermissionTo('auctions.create'));
        $this->assertFalse($citizen->hasPermissionTo('auctions.publish'));
        $this->assertFalse($citizen->hasPermissionTo('users.blacklist'));
    }

    public function test_huissier_can_sign_documents_and_confirm_payments(): void
    {
        $huissier = Role::findByName(UserRole::HUISSIER->value);

        $this->assertTrue($huissier->hasPermissionTo('documents.sign'));
        $this->assertTrue($huissier->hasPermissionTo('payments.confirm'));
    }

    public function test_role_can_be_assigned_to_user(): void
    {
        $user = User::create([
            'nin' => '109823041175663867',
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'دور',
            'phone' => '0555000123',
            'email' => 'roletest@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::HUISSIER,
        ]);

        $user->assignRole(UserRole::HUISSIER->value);

        $this->assertTrue($user->hasRole(UserRole::HUISSIER->value));
        $this->assertTrue($user->can('documents.sign'));
    }
}
