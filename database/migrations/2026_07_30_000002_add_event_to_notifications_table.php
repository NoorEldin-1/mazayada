<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Machine-readable event type on in-app notifications.
 *
 * `channel` records HOW a notification was delivered (IN_APP / EMAIL / PUSH), not
 * WHAT happened — so a client had no way to tell "you were outbid" from "you won"
 * apart from parsing the translated title. `event` stores the key that already
 * selects the copy (AuctionEventNotification::$event: outbid, auction_won,
 * payment_confirmed, …) and is exposed as `type` by the API.
 *
 * Nullable: rows written before this migration keep a null event, and clients
 * must fall back to the title/body for those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('event', 64)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('event');
        });
    }
};
