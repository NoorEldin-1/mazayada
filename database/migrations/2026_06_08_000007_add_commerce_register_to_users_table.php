<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §2.3 — professional (commercial) customs goods require the bidder to hold a
 * valid Commerce Register (Registre du Commerce). Gated at registration when
 * the auction has requires_commerce_register = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('commerce_register_no', 30)->nullable()->after('nis');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('commerce_register_no');
        });
    }
};
