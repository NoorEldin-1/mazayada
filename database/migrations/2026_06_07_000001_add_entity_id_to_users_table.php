<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-tenancy backbone (spec Section 8 — per-entity RBAC).
     *
     * A NULL entity_id means a platform-wide account (citizens AND the
     * SUPER_ADMIN). A non-null value binds a staff member to exactly one
     * government entity, which the EntityScope global scope uses to isolate
     * that staff member's view inside the admin dashboard.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('entity_id')
                ->nullable()
                ->after('role')
                ->constrained('entities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });
    }
};
