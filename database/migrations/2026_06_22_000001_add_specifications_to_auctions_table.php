<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-authored, repeatable asset specifications — an ordered list of
     * { title, description } blocks (e.g. "Engine", "Colour"). Stored inline as
     * JSON since they are always rendered with the auction and never queried
     * independently. Each row keeps an Arabic value (required) and an optional
     * French one, mirroring the description_ar/description_fr convention.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->json('specifications')->nullable()->after('description_fr');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('specifications');
        });
    }
};
