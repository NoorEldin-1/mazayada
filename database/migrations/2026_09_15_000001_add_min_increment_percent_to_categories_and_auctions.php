<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sector minimum-increment rule (client edits 11 · 12).
 *
 * A sector (القطاع) is the auction category. Each sector carries a default
 * minimum bid increment as a percentage of the current price; the organising
 * side may override it per auction when the session is created. A bid below
 * current_price × (1 + percent/100) is refused by BiddingService.
 *
 * 0 keeps the historical rule ("anything above the current price").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('min_increment_percent', 5, 2)->default(0)->after('icon');
        });

        Schema::table('auctions', function (Blueprint $table) {
            // Null = inherit the sector's percentage.
            $table->decimal('min_increment_percent', 5, 2)->nullable()->after('deposit_percent');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('min_increment_percent');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('min_increment_percent');
        });
    }
};
