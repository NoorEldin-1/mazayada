<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id');
            $table->unsignedBigInteger('amount');
            $table->timestamp('bid_time');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['auction_id', 'amount']);
            $table->index(['auction_id', 'bid_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
