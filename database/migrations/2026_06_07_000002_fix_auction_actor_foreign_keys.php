<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repoint auctions.created_by / appraiser_id from entity_users(id) to
     * users(id). Staff are now canonical Users (multi-tenancy), and
     * AdminAuctionController::store already saves auth()->id() (a User id) into
     * created_by — the old FK would reject that on MySQL.
     *
     * The baseline migration (0001_01_01_000008) was corrected for fresh
     * installs, so this only needs to repair MySQL databases created earlier.
     * SQLite (tests) always builds from the corrected baseline → no-op here.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['created_by', 'appraiser_id'] as $column) {
            $current = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'auctions')
                ->where('COLUMN_NAME', $column)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('REFERENCED_TABLE_NAME');

            // Already correct (fresh install) or column missing — nothing to do.
            if ($current !== 'entity_users') {
                continue;
            }

            Schema::table('auctions', function ($table) use ($column) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['created_by', 'appraiser_id'] as $column) {
            Schema::table('auctions', function ($table) use ($column) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on('entity_users')->nullOnDelete();
            });
        }
    }
};
