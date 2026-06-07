<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle fields for the full auction process (spec §4):
 *  - asset_class: drives the final-payment deadline, customs 20% rule, and
 *    whether the movables hammer-fee tiers apply (Decree 97-33).
 *  - inspection window (§4 step 4) and original-owner designation (§6.4).
 *  - extension cap (§6.3) and the close/settlement anchors used by the
 *    scheduled commands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('asset_class', 15)->default('MOVABLE')->after('auction_type');
            $table->boolean('requires_newspaper_announcement')->default(false)->after('requires_commerce_register');

            // Inspection window (§4 step 4).
            $table->dateTime('inspection_start')->nullable()->after('requires_newspaper_announcement');
            $table->dateTime('inspection_end')->nullable()->after('inspection_start');
            $table->string('inspection_location')->nullable()->after('inspection_end');

            // Auto-extension cap (§6.3).
            $table->unsignedSmallInteger('max_extensions')->default(10)->after('extension_duration_minutes');
            $table->unsignedSmallInteger('extension_count')->default(0)->after('max_extensions');

            // Original-owner priority (§6.4) — the Huissier designates a NIN at listing.
            $table->char('original_owner_nin', 18)->nullable()->after('appraiser_id');

            // Anchors for the scheduled lifecycle commands (idempotency + deadlines).
            $table->timestamp('closed_at')->nullable()->after('final_price');
            $table->timestamp('settled_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn([
                'asset_class', 'requires_newspaper_announcement',
                'inspection_start', 'inspection_end', 'inspection_location',
                'max_extensions', 'extension_count', 'original_owner_nin',
                'closed_at', 'settled_at',
            ]);
        });
    }
};
