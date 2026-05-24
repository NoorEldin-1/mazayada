<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor_id');
            $table->string('actor_role', 30);
            $table->string('action', 50);
            $table->string('resource_type', 30);
            $table->string('resource_id');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
