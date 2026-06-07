<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §10.3 — the citizen must acknowledge reading the condition book (timestamped)
 * before registering. §8 — track when a defaulting winner was blacklisted via
 * this participation so settlement is idempotent and auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_participants', function (Blueprint $table) {
            $table->timestamp('condition_book_acknowledged_at')->nullable()->after('book_purchased');
            $table->boolean('blacklisted_for_default')->default(false)->after('condition_book_acknowledged_at');
        });
    }

    public function down(): void
    {
        Schema::table('auction_participants', function (Blueprint $table) {
            $table->dropColumn(['condition_book_acknowledged_at', 'blacklisted_for_default']);
        });
    }
};
