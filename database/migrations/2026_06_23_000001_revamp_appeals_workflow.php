<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revamps the appeals (الطعون) feature into a three-party workflow
 * (citizen → platform admin → organising entity → platform admin → citizen).
 *
 *  - auction_id becomes REQUIRED: an appeal is always filed against a specific
 *    auction the user took part in (the standalone, auction-less appeal is gone).
 *  - New columns carry the entity's decision + the audit timestamps for each
 *    handoff. The platform admin's final note keeps the existing admin_response.
 *  - The old status vocabulary (SUBMITTED/UNDER_REVIEW/RESOLVED/ESCALATED) is
 *    remapped onto the new internal state machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Orphan appeals (no auction) cannot exist in the new model — drop them.
        DB::table('appeals')->whereNull('auction_id')->delete();

        // Remap the legacy status vocabulary onto the new state machine.
        DB::table('appeals')->where('status', 'SUBMITTED')->update(['status' => 'PENDING']);
        DB::table('appeals')->where('status', 'UNDER_REVIEW')->update(['status' => 'PENDING']);
        DB::table('appeals')->where('status', 'ESCALATED')->update(['status' => 'FORWARDED_TO_ENTITY']);
        DB::table('appeals')->where('status', 'RESOLVED')->update(['status' => 'APPROVED']);
        // REJECTED keeps its name.

        Schema::table('appeals', function (Blueprint $table) {
            // auction_id: nullable → required, FK nullOnDelete → cascadeOnDelete.
            $table->dropForeign(['auction_id']);
        });

        Schema::table('appeals', function (Blueprint $table) {
            $table->uuid('auction_id')->nullable(false)->change();
            $table->string('status', 20)->default('PENDING')->change();

            // The organising entity's decision on a forwarded appeal.
            $table->string('entity_decision', 20)->nullable()->after('admin_response');
            $table->text('entity_response')->nullable()->after('entity_decision');

            // Handoff audit trail.
            $table->uuid('forwarded_by')->nullable()->after('entity_response');
            $table->uuid('resolved_by')->nullable()->after('forwarded_by');
            $table->timestamp('forwarded_at')->nullable()->after('resolved_at');
            $table->timestamp('entity_decided_at')->nullable()->after('forwarded_at');

            $table->foreign('auction_id')->references('id')->on('auctions')->cascadeOnDelete();
            $table->foreign('forwarded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['auction_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('appeals', function (Blueprint $table) {
            $table->dropForeign(['auction_id']);
            $table->dropForeign(['forwarded_by']);
            $table->dropForeign(['resolved_by']);
            $table->dropIndex(['auction_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'entity_decision', 'entity_response',
                'forwarded_by', 'resolved_by', 'forwarded_at', 'entity_decided_at',
            ]);
        });

        Schema::table('appeals', function (Blueprint $table) {
            $table->uuid('auction_id')->nullable()->change();
            $table->string('status', 20)->default('SUBMITTED')->change();
            $table->foreign('auction_id')->references('id')->on('auctions')->nullOnDelete();
        });
    }
};
