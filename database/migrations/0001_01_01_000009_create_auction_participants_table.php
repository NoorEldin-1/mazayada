<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id');
            $table->boolean('is_original_owner')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->boolean('entry_fee_paid')->default(false);
            $table->boolean('book_purchased')->default(false);
            $table->timestamp('registered_at');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['auction_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_participants');
    }
};
