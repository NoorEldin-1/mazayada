<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Premium subscriptions + notification preferences (client edits 24-30).
 *
 * subscription_plans         admin-managed MONTHLY / YEARLY plans (price in centimes)
 * subscriptions              one row per purchase; paid through the payments ledger
 *                            (PaymentType::SUBSCRIPTION); users.premium_until stays the
 *                            single source of truth for isPremium()
 * notification_preferences   channels + preferred auction categories per user
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name_ar');
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->string('period', 10); // MONTHLY | YEARLY
            $table->unsignedBigInteger('price');
            // {"ar": [...], "fr": [...], "en": [...]} — translated feature bullets.
            $table->json('features')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->string('status', 15)->default('PENDING'); // PENDING | ACTIVE | EXPIRED | CANCELLED
            $table->unsignedBigInteger('price'); // frozen at purchase (centimes)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expiry_reminded_at')->nullable();
            $table->uuid('payment_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans');
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('new_auction_alerts')->default(true);
            // Category ids the citizen follows; empty = every category.
            $table->json('auction_categories')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
