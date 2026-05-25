<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Permissions are grouped by resource and verb to match the platform's
     * RBAC matrix in the technical specification (Section 8).
     *
     * Keep the format `{resource}.{action}` so policies and Gate checks
     * can rely on a predictable naming scheme.
     */
    private const PERMISSIONS = [
        // Auctions
        'auctions.viewAny',
        'auctions.view',
        'auctions.create',
        'auctions.update',
        'auctions.publish',
        'auctions.cancel',
        'auctions.delete',
        'auctions.extend',
        'auctions.appraise',

        // Bids
        'bids.place',
        'bids.viewAny',

        // Condition Book / Documents
        'documents.upload',
        'documents.sign',
        'documents.download',

        // Payments
        'payments.viewAny',
        'payments.refund',
        'payments.confirm',

        // KYC
        'kyc.review',
        'kyc.approve',
        'kyc.reject',

        // Users / Blacklist
        'users.viewAny',
        'users.blacklist',
        'users.suspend',

        // Entities
        'entities.manage',
        'entities.members.manage',

        // Categories
        'categories.manage',

        // System
        'system.parameters.manage',
        'system.auditlogs.view',

        // Appeals
        'appeals.viewAny',
        'appeals.respond',
        'appeals.create',
    ];

    private const ROLE_PERMISSIONS = [
        UserRole::SUPER_ADMIN->value => ['*'],

        UserRole::ENTITY_HEAD->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.create', 'auctions.update',
            'auctions.publish', 'auctions.cancel', 'auctions.extend',
            'bids.viewAny',
            'documents.upload', 'documents.sign', 'documents.download',
            'payments.viewAny', 'payments.confirm',
            'kyc.review',
            'entities.members.manage',
            'appeals.viewAny', 'appeals.respond',
            'system.auditlogs.view',
        ],

        UserRole::CONTENT_ADMIN->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.create', 'auctions.update',
            'auctions.publish',
            'documents.upload', 'documents.download',
            'categories.manage',
        ],

        UserRole::APPRAISER->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.appraise',
            'documents.upload',
        ],

        UserRole::HUISSIER->value => [
            'auctions.viewAny', 'auctions.view',
            'documents.upload', 'documents.sign', 'documents.download',
            'payments.viewAny', 'payments.confirm',
            'appeals.viewAny',
        ],

        UserRole::COMMITTEE_MEMBER->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.viewAny',
            'documents.download',
        ],

        UserRole::CITIZEN->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.place',
            'documents.download',
            'appeals.create',
        ],

        UserRole::PREMIUM_CITIZEN->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.place',
            'documents.download',
            'appeals.create',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions === ['*']) {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
