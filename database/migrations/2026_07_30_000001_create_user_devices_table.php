<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Push-notification device registry for the mobile client.
 *
 * One row per (user, device token). The token is globally unique — reinstalling
 * the app or signing in as a different user re-issues the SAME token to a new
 * owner, so registering re-points the existing row instead of duplicating it
 * (otherwise the previous owner would keep receiving the new user's push).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            // FCM registration tokens are ~150-250 chars; APNs tokens 64 hex.
            // 255 keeps the unique index within InnoDB's key-length budget.
            $table->string('token', 255)->unique();
            $table->string('platform', 10); // ios | android
            $table->string('locale', 5)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
