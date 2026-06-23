<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The condition book (دفتر الشروط) is no longer a free public download — it must
 * be purchased before it can be read (gated in DocumentPolicy). Existing books
 * were generated with is_public = true, so flip them to false to bring them
 * under the new paywall. New books are generated private by DocumentService.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents')) {
            return;
        }

        DB::table('documents')
            ->where('type', 'CONDITION_BOOK')
            ->update(['is_public' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('documents')) {
            return;
        }

        DB::table('documents')
            ->where('type', 'CONDITION_BOOK')
            ->update(['is_public' => true]);
    }
};
