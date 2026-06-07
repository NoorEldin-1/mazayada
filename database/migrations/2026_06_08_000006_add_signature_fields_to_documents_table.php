<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signature + storage metadata for generated documents (spec §9.3, §10.2).
 * `signature` is the HMAC embedded in the QR verification payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('qr_payload');
            $table->string('disk', 20)->default('documents')->after('file_path');
            $table->string('mime')->nullable()->after('file_size');
            $table->json('meta')->nullable()->after('is_public');
            $table->uuid('user_id')->nullable()->after('auction_id'); // owner (winner / purchaser)

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['auction_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['auction_id', 'type']);
            $table->dropColumn(['signature', 'disk', 'mime', 'meta', 'user_id']);
        });
    }
};
