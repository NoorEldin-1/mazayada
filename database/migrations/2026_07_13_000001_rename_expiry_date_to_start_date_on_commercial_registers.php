<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Commercial Register (السجل التجاري) date column was originally modelled as
 * an *expiry* date, but it actually records when the register was issued — its
 * *start* date. Rename the column accordingly; the expiry/validity gating that
 * hung off it is dropped (a register is now "valid" as soon as it is approved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_registers', function (Blueprint $table) {
            $table->renameColumn('expiry_date', 'start_date');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_registers', function (Blueprint $table) {
            $table->renameColumn('start_date', 'expiry_date');
        });
    }
};
