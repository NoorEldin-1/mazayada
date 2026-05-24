<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('auction_id')->nullable();
            $table->string('payment_type', 20);
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('PENDING');
            $table->string('transaction_ref')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('auction_id')->references('id')->on('auctions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
