<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('auction_id')->nullable();
            $table->string('type', 30);
            $table->string('title');
            $table->string('file_path');
            $table->unsignedInteger('file_size')->nullable();
            $table->string('qr_payload')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->foreign('auction_id')->references('id')->on('auctions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
