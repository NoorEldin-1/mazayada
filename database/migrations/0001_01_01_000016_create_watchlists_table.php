<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('auction_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('auction_id')->references('id')->on('auctions')->cascadeOnDelete();
            $table->primary(['user_id', 'auction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};
