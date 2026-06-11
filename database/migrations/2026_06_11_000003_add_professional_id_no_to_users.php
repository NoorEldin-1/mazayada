<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the entity-staff "professional identification number"
     * (الرقم التعريف المهني). Kept nullable at the DB level — it is required by
     * the staff-create form only — so existing rows and the strict-model local
     * env are unaffected. Unique to prevent duplicate professional numbers
     * (MySQL allows multiple NULLs under a unique index).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('professional_id_no')->nullable()->unique()->after('nin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['professional_id_no']);
            $table->dropColumn('professional_id_no');
        });
    }
};
