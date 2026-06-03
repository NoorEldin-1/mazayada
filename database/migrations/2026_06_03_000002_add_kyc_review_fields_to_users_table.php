<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When the citizen finishes the KYC form + document upload and
            // submits for review. Distinguishes a real submission (UNDER_REVIEW)
            // from a freshly-registered account that never started KYC (PENDING).
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_completed_at');

            // Reason shown to the citizen when an admin rejects their submission,
            // so they know what to fix before resubmitting.
            $table->string('kyc_rejection_reason')->nullable()->after('kyc_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_submitted_at', 'kyc_rejection_reason']);
        });
    }
};
