<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make every account bound to a government entity read-only.
     *
     * Product rule (see UserRole::ENTITY_VIEWER): an entity-bound account only
     * views — never mutates — its own entity's auctions and appeals; all auction
     * management is centralised on the platform. Existing staff seeded with a
     * write role (e.g. ENTITY_HEAD) are migrated to ENTITY_VIEWER here.
     *
     * Safe on a fresh database: roles are seeded AFTER migrations run, so when the
     * ENTITY_VIEWER role does not exist yet there are also no entity-bound users
     * to convert and this becomes a no-op (fresh installs get read-only accounts
     * straight from the seeders / the entity & staff controllers).
     *
     * NOTE: On a normal deploy this runs in the `migrate` step — BEFORE roles are
     * seeded — so it no-ops on an existing database. The CANONICAL conversion that
     * actually downgrades pre-existing staff lives in RolesPermissionsSeeder
     * (db:seed step, after the role exists). This migration only does the work when
     * the role already happens to exist at migrate time; both paths are idempotent.
     */
    public function up(): void
    {
        $viewer = UserRole::ENTITY_VIEWER->value;

        $roleId = DB::table('roles')->where('name', $viewer)->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            return; // roles not seeded yet — nothing to convert on a fresh DB
        }

        $morph = (new User)->getMorphClass();

        // Individual staff people bound to an entity (institutional accounts are
        // already ENTITY_VIEWER and are excluded by account_type).
        $userIds = DB::table('users')
            ->whereNotNull('entity_id')
            ->where('account_type', 'PERSON')
            ->pluck('id');

        if ($userIds->isNotEmpty()) {
            // Legacy enum column on users.
            DB::table('users')->whereIn('id', $userIds)->update(['role' => $viewer]);

            // Spatie pivot: drop any existing role for these users, then assign viewer.
            DB::table('model_has_roles')
                ->where('model_type', $morph)
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::table('model_has_roles')->insert(
                $userIds->map(fn ($id) => [
                    'role_id' => $roleId,
                    'model_type' => $morph,
                    'model_id' => $id,
                ])->all()
            );
        }

        // Every staff mirror row is a viewer now.
        DB::table('entity_users')->update(['role' => $viewer]);
    }

    public function down(): void
    {
        // One-way data correction — the prior per-user roles are not recorded.
    }
};
