<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-authored clauses for the award document (وثيقة الترسية / procès-verbal
     * d'adjudication). Free-form text the entity staff writes per auction; it is
     * rendered verbatim in the "Terms & clauses" section of the generated award
     * PDF (produced at auction close), falling back to the static default text
     * when left blank. Arabic value is primary, French optional — mirroring
     * condition_terms_ar/condition_terms_fr.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->text('award_terms_ar')->nullable()->after('condition_terms_fr');
            $table->text('award_terms_fr')->nullable()->after('award_terms_ar');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['award_terms_ar', 'award_terms_fr']);
        });
    }
};
