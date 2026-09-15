<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auction sessions + publication rights (client edits 5-10, 13-17, 26).
 *
 * Sessions — a reschedule creates a NEW auction row (its own bids, participants
 * and documents) chained to the previous one:
 *   session_code        stable human reference, e.g. SES-2026-0142
 *   session_round       1 for the original, 2 for the first reschedule, …
 *   parent_auction_id   the session this one re-runs
 *   root_auction_id     the original session of the chain (null on the original)
 *   reduction_percent   opening-price reduction applied to THIS session
 *   original_opening_price  the chain's opening price before any reduction
 *
 * Publication rights — the organising entity pays the platform to publish:
 *   publication_packages      admin-managed display spaces with prices
 *   publication_priority      NORMAL | PRIORITY (priority costs more, lists first)
 *   publication_fee(_paid_at|_ref)  the computed fee and its settlement
 *
 * New-auction alerts — premium_alerted_at / public_alerted_at record the two
 * dispatch waves (subscribers first, everyone else after a delay).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name_ar');
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            // Display space the package buys (LISTING | CATEGORY_TOP | HOMEPAGE).
            $table->string('display_area', 20)->default('LISTING');
            // Centimes. price = NORMAL publication; priority_price = surcharge for PRIORITY.
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('priority_price')->default(0);
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->string('session_code', 20)->nullable()->unique()->after('id');
            $table->unsignedSmallInteger('session_round')->default(1)->after('session_code');
            $table->uuid('parent_auction_id')->nullable()->after('session_round');
            $table->uuid('root_auction_id')->nullable()->index()->after('parent_auction_id');
            $table->decimal('reduction_percent', 5, 2)->default(0)->after('root_auction_id');
            $table->unsignedBigInteger('original_opening_price')->nullable()->after('reduction_percent');

            $table->string('publication_priority', 10)->default('NORMAL')->index();
            $table->unsignedBigInteger('publication_package_id')->nullable();
            $table->unsignedBigInteger('publication_fee')->default(0);
            $table->timestamp('publication_fee_paid_at')->nullable();
            $table->string('publication_fee_ref', 100)->nullable();

            $table->timestamp('premium_alerted_at')->nullable();
            $table->timestamp('public_alerted_at')->nullable();

            $table->foreign('publication_package_id')->references('id')->on('publication_packages')->nullOnDelete();
        });

        // Backfill a session code for every existing auction, numbered per year of
        // creation in creation order. Already-public auctions are marked as alerted
        // so the new alert dispatcher never blasts the back catalogue.
        $counters = [];
        DB::table('auctions')->orderBy('created_at')->orderBy('id')
            ->select(['id', 'created_at', 'status'])
            ->get()
            ->each(function ($row) use (&$counters) {
                $year = $row->created_at ? substr((string) $row->created_at, 0, 4) : date('Y');
                $counters[$year] = ($counters[$year] ?? 0) + 1;

                $update = ['session_code' => sprintf('SES-%s-%04d', $year, $counters[$year])];
                if ($row->status !== 'DRAFT') {
                    $update['premium_alerted_at'] = now();
                    $update['public_alerted_at'] = now();
                }

                DB::table('auctions')->where('id', $row->id)->update($update);
            });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropForeign(['publication_package_id']);
            $table->dropUnique(['session_code']);
            $table->dropIndex(['root_auction_id']);
            $table->dropIndex(['publication_priority']);
            $table->dropColumn([
                'session_code', 'session_round', 'parent_auction_id', 'root_auction_id',
                'reduction_percent', 'original_opening_price',
                'publication_priority', 'publication_package_id', 'publication_fee',
                'publication_fee_paid_at', 'publication_fee_ref',
                'premium_alerted_at', 'public_alerted_at',
            ]);
        });

        Schema::dropIfExists('publication_packages');
    }
};
