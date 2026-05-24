<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('role', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_users');
    }
};
