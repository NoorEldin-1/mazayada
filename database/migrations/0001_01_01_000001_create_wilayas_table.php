<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayas', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 2)->unique();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayas');
    }
};
