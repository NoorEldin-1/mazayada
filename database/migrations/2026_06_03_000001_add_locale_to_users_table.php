<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Preferred UI language. Defaults to Arabic (the platform's primary
            // language). Persisted so the choice follows the user across devices
            // and sessions; an explicit switch updates this column.
            $table->string('locale', 2)->default('ar')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
