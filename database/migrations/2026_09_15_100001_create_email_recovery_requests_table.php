<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Lost my email" recovery requests. A citizen who can no longer reach the
 * mailbox on their account proves their identity (NIN + birth date + phone +
 * a selfie holding the ID card) and asks for a new address; an admin compares
 * the selfie with the KYC documents and approves or rejects the change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_recovery_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Identity data exactly as the citizen typed it (kept for the review
            // trail even though it had to match the account to be accepted).
            $table->string('nin', 18);
            $table->date('birth_date');
            $table->string('phone', 20);

            // The address being replaced and the requested one.
            $table->string('old_email')->nullable();
            $table->string('new_email');

            // Selfie holding the ID card — PRIVATE "local" disk only (Law 18-07),
            // streamed to reviewers through a gated route.
            $table->string('selfie_with_id_path');

            $table->string('status', 20)->default('PENDING');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // The admin queue filters on status; the status lookup goes by NIN.
            $table->index('status');
            $table->index(['nin', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_recovery_requests');
    }
};
