<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gateway-tracking fields for real payment flows (spec §7) on top of the
 * existing payments table. Money stays in integer centimes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 30)->nullable()->after('status');
            $table->string('gateway_ref')->nullable()->after('gateway');
            $table->json('gateway_payload')->nullable()->after('gateway_ref');
            $table->json('payable_meta')->nullable()->after('gateway_payload');
            $table->timestamp('due_at')->nullable()->after('confirmed_at');
            $table->timestamp('failed_at')->nullable()->after('refunded_at');
            $table->timestamp('forfeited_at')->nullable()->after('failed_at');

            $table->index(['auction_id', 'payment_type', 'status']);
            $table->index('gateway_ref');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['auction_id', 'payment_type', 'status']);
            $table->dropIndex(['gateway_ref']);
            $table->dropColumn([
                'gateway', 'gateway_ref', 'gateway_payload', 'payable_meta',
                'due_at', 'failed_at', 'forfeited_at',
            ]);
        });
    }
};
