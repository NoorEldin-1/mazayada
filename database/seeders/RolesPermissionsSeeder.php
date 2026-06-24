<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\EntityUser;
use App\Models\User;
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
        'appeals.respond', // platform admin: forward / confirm / reject-at-intake
        'appeals.decide',  // organising entity: approve / reject a forwarded appeal
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

        // Read-only entity account (institutional login + its staff). Strictly
        // observes its own entity's auctions — no create/update/publish/cancel/
        // extend/delete/manage. The ONE deliberate write exception is appeals:
        // an appeal the platform forwards to this entity may be approved/rejected
        // (appeals.decide). Per-entity isolation is enforced by EntityScope + the
        // policies' sameEntity() check.
        UserRole::ENTITY_VIEWER->value => [
            'auctions.viewAny', 'auctions.view',
            'bids.viewAny',
            'payments.viewAny',
            'documents.download',
            'appeals.viewAny', 'appeals.decide',
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

        $this->enforceEntityAccountsAreReadOnly();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Hard product rule: any account bound to a government entity is read-only
     * (UserRole::ENTITY_VIEWER) — it only views its entity's auctions & appeals;
     * all auction management is centralised on the platform.
     *
     * This is the CANONICAL place for the conversion because it runs on every
     * deploy (db:seed step) AFTER the ENTITY_VIEWER role exists, so it reliably
     * downgrades any pre-existing staff that still hold a write role. The
     * standalone data migration runs BEFORE roles are seeded, so it cannot be
     * relied on for an already-populated database. Idempotent — rows already on
     * ENTITY_VIEWER are skipped.
     */
    private function enforceEntityAccountsAreReadOnly(): void
    {
        $viewer = UserRole::ENTITY_VIEWER->value;

        User::query()
            ->whereNotNull('entity_id')
            ->where('role', '!=', $viewer)
            ->get()
            ->each(function (User $user) use ($viewer) {
                $user->update(['role' => $viewer]);
                $user->syncRoles([$viewer]);
            });

        EntityUser::where('role', '!=', $viewer)->update(['role' => $viewer]);
    }
}
