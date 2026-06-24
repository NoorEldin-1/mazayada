<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-authored terms for the condition book (كراسة الشروط / cahier des
     * charges). Free-form text that the entity staff writes per auction; it is
     * rendered verbatim in the "Terms" section of the generated condition-book
     * PDF, falling back to the static default text when left blank. Arabic value
     * is primary, French optional — mirroring description_ar/description_fr.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->text('condition_terms_ar')->nullable()->after('specifications');
            $table->text('condition_terms_fr')->nullable()->after('condition_terms_ar');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['condition_terms_ar', 'condition_terms_fr']);
        });
    }
};
