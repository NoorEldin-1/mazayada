<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auction reports (تقارير المزادات). Each row is one issued report: a frozen
     * snapshot of the auction's latest details, backed by a signed, verifiable PDF
     * (documents row). The admin may issue any number of reports per auction; the
     * newest is the one the "view last report" action resolves.
     *
     * The referral columns mirror the appeals workflow's admin→entity handoff: a
     * report is invisible to the organising entity until the platform admin refers
     * it (referred_to_entity_at is stamped). Per-entity isolation is transitive
     * through the auction (EntityScope), exactly like appeals.
     */
    public function up(): void
    {
        Schema::create('auction_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('auction_id');
            $table->uuid('document_id')->nullable(); // the signed PDF this report points at
            $table->unsignedInteger('sequence_no')->default(1); // "report #N" per auction
            $table->uuid('generated_by')->nullable(); // staff who issued it
            $table->json('snapshot')->nullable(); // lightweight summary for the module list
            $table->timestamp('referred_to_entity_at')->nullable();
            $table->uuid('referred_by')->nullable();
            $table->timestamps();

            $table->foreign('auction_id')->references('id')->on('auctions')->cascadeOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['auction_id', 'sequence_no']);
            $table->index('referred_to_entity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_reports');
    }
};
