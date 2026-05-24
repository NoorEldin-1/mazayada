<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('auction_id')->nullable();
            $table->string('subject');
            $table->text('reason');
            $table->string('status', 20)->default('SUBMITTED');
            $table->text('admin_response')->nullable();
            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('auction_id')->references('id')->on('auctions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeals');
    }
};
