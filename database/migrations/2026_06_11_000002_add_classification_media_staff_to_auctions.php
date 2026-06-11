<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classification gains a commune (البلدية) + optional mayor name; the asset
     * media gains a single short video alongside the photos; and an auction may
     * be tied to a specific entity staff member (موظف الجهة).
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            // Classification: municipality scoped under the wilaya + optional mayor.
            $table->unsignedBigInteger('commune_id')->nullable()->after('wilaya_id');
            $table->string('mayor_name')->nullable()->after('commune_id');

            // A single short asset video (path on the public disk), beside photos.
            $table->string('video')->nullable()->after('photos');

            // Optional entity staff member responsible for this auction.
            $table->uuid('entity_user_id')->nullable()->after('entity_id');

            $table->foreign('commune_id')->references('id')->on('communes')->nullOnDelete();
            $table->foreign('entity_user_id')->references('id')->on('entity_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['entity_user_id']);
            $table->dropColumn(['commune_id', 'mayor_name', 'video', 'entity_user_id']);
        });
    }
};
