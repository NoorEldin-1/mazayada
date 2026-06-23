<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participation deposit (رسوم المشاركة / التأمين) is now a PERCENTAGE of the
 * opening price, set per-auction (default 10%). The absolute centimes value is
 * still stored in `deposit_amount` (derived at write time) so the payment +
 * settlement flows keep reading a concrete amount; this column records the rate
 * the admin chose so the edit form can re-display it and recompute on changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->decimal('deposit_percent', 5, 2)->default(10.00)->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('deposit_percent');
        });
    }
};
