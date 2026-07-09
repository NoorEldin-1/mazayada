<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commercial Register (السجل التجاري) submissions. One record per user (unique
 * user_id): the user (re)fills it, an admin approves/rejects it, and an APPROVED
 * + non-expired record is what unlocks bidding on auctions that carry
 * requires_commerce_register. Mirrors the KYC review pipeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Text data supplied by the user.
            $table->string('company_name');
            $table->string('register_number');
            $table->string('tax_number');
            $table->string('activity_type');
            $table->date('expiry_date');

            // Uploaded scans (PDF or image) — stored on the private "local" disk,
            // reachable only through the gated document() routes.
            $table->string('register_document_path')->nullable();
            $table->string('tax_card_document_path')->nullable();

            // Review state.
            $table->string('status')->default('PENDING');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // The admin queue filters on status; the badge does a COUNT on it.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_registers');
    }
};
