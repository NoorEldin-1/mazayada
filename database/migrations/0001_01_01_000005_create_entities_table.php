<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('type', 30);
            $table->unsignedTinyInteger('wilaya_id');
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('wilaya_id')->references('id')->on('wilayas');
            $table->foreign('commune_id')->references('id')->on('communes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
