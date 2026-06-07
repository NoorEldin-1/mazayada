<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runtime-editable platform parameters (spec §8.2 — Super Admin system
     * parameters). DB-backed because production runs `config:cache`, so editing
     * config/mazayada.php at runtime would have no effect. The setting() helper
     * reads here first and falls back to config/mazayada.php.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string|int|bool|float
            $table->string('group', 50)->default('general');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
