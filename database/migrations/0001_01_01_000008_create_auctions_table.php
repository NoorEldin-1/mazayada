<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained();
            $table->unsignedBigInteger('category_id');
            $table->string('title_ar');
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->string('condition', 10)->default('GOOD');
            $table->unsignedInteger('unit_count')->default(1);
            $table->string('asset_location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('opening_price');
            $table->unsignedBigInteger('deposit_amount');
            $table->unsignedBigInteger('entry_fee');
            $table->unsignedBigInteger('book_price')->default(300000);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedSmallInteger('extension_trigger_seconds')->default(30);
            $table->unsignedSmallInteger('extension_duration_minutes')->default(5);
            $table->string('status', 20)->default('DRAFT');
            $table->uuid('winner_user_id')->nullable();
            $table->unsignedBigInteger('final_price')->nullable();
            $table->string('auction_type', 10)->default('SALE');
            $table->unsignedSmallInteger('lease_duration_years')->nullable();
            $table->unsignedSmallInteger('lease_renewals')->default(2);
            $table->boolean('requires_commerce_register')->default(false);
            $table->uuid('created_by')->nullable();
            $table->uuid('appraiser_id')->nullable();
            $table->unsignedTinyInteger('wilaya_id')->nullable();
            $table->text('photos')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('winner_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('entity_users')->nullOnDelete();
            $table->foreign('appraiser_id')->references('id')->on('entity_users')->nullOnDelete();
            $table->foreign('wilaya_id')->references('id')->on('wilayas');
            $table->index('status');
            $table->index(['status', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
