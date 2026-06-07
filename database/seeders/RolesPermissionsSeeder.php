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
        'auctions.comment', // committee opinions on a file (spec §8.1 — UI in a later phase)

        // Bids
        'bids.place',
        'bids.viewAny',

        // Condition Book / Documents
        'documents.upload',
        'documents.sign',
        'documents.download',
        'documents.generate', // generate signed PDFs (award / condition book / receipt / delivery report)

        // Payments
        'payments.viewAny',
        'payments.refund',
        'payments.confirm',

        // Inspection Q&A (spec §4 step 4)
        'inspections.ask',
        'inspections.answer',

        // Delivery (spec §4 step 9)
        'deliveries.manage',

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
            'auctions.publish', 'auctions.cancel', 'auctions.extend', 'auctions.comment',
            'bids.viewAny',
            'documents.upload', 'documents.sign', 'documents.download', 'documents.generate',
            'payments.viewAny', 'payments.confirm', 'payments.refund',
            'kyc.review',
            'entities.members.manage',
            'inspections.answer', 'deliveries.manage',
            'appeals.viewAny', 'appeals.respond',
            'system.auditlogs.view',
        ],

        UserRole::CONTENT_ADMIN->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.create', 'auctions.update',
            'auctions.publish',
            'documents.upload', 'documents.download', 'documents.generate',
            'inspections.answer',
            'categories.manage',
        ],

        UserRole::APPRAISER->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.appraise',
            'documents.upload',
        ],

        UserRole::HUISSIER->value => [
            'auctions.viewAny', 'auctions.view',
            'documents.upload', 'documents.sign', 'documents.download', 'documents.generate',
            'payments.viewAny', 'payments.confirm', 'payments.refund',
            'inspections.answer', 'deliveries.manage',
            'appeals.viewAny',
        ],

        UserRole::COMMITTEE_MEMBER->value => [
            'auctions.viewAny', 'auctions.view', 'auctions.comment',
            'bids.viewAny',
            'documents.download',
            'appeals.viewAny',
        ],

        UserRole::CITIZEN->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.place',
            'documents.download',
            'inspections.ask',
            'appeals.create',
        ],

        UserRole::PREMIUM_CITIZEN->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.place',
            'documents.download',
            'inspections.ask',
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
