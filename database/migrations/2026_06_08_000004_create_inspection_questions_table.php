<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bidder Q&A during the inspection window (spec §4 step 4 — Art. 7 condition
 * book + Customs Art. 373). Answered questions are shown publicly on the auction
 * page so the competition principle is preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id');                 // the asking citizen
            $table->text('question');
            $table->text('answer')->nullable();
            $table->uuid('answered_by')->nullable(); // staff User who answered
            $table->string('status', 12)->default('PENDING');
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('answered_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['auction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_questions');
    }
};
