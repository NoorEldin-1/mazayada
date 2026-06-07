<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical hand-over of a won asset (spec §4 step 9). The signed delivery
 * report (محضر التسليم) is generated as a Document and linked here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id'); // the winner
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('status', 12)->default('SCHEDULED');
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('report_document_id')->nullable();
            $table->uuid('created_by')->nullable(); // staff User
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('report_document_id')->references('id')->on('documents')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['auction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
