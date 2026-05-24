<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nin', 18)->unique();
            $table->string('id_card_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('license_number')->nullable();
            $table->string('first_name_ar');
            $table->string('last_name_ar');
            $table->string('first_name_fr')->nullable();
            $table->string('last_name_fr')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_fullname')->nullable();
            $table->date('birth_date');
            $table->string('birth_place')->nullable();
            $table->string('phone', 10)->unique();
            $table->string('email')->unique();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->string('postal_code', 5)->nullable();
            $table->string('profession')->nullable();
            $table->string('nif', 15)->nullable();
            $table->string('nis', 18)->nullable();
            $table->string('rip')->nullable();
            $table->unsignedBigInteger('expected_income')->nullable();
            $table->string('kyc_status', 20)->default('PENDING');
            $table->timestamp('kyc_completed_at')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->string('blacklist_reason')->nullable();
            $table->string('account_status', 20)->default('ACTIVE');
            $table->timestamp('premium_until')->nullable();
            $table->string('secret_question')->nullable();
            $table->string('secret_answer')->nullable();
            $table->string('password');
            $table->string('role', 30)->default('CITIZEN');
            $table->boolean('phone_verified')->default(false);
            $table->boolean('email_verified')->default(false);
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('commune_id')->references('id')->on('communes')->nullOnDelete();
            $table->index('kyc_status');
            $table->index('role');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
