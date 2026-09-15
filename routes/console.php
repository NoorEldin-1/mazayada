<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auctions:activate')->everyMinute();
Schedule::command('auctions:close')->everyMinute();
// §4 step 8 — settle deposits (refund losers / forfeit defaulters) after the
// final-payment deadline; §10.1 — remind winners before the deadline.
Schedule::command('auctions:settle-deposits')->hourly();
Schedule::command('auctions:remind-final-payment')->daily();
Schedule::command('kyc:suspend-stale')->daily();
// Edits 26 · 28 — new-auction alerts (Premium wave first, others after a delay).
Schedule::command('auctions:dispatch-alerts')->everyMinute()->withoutOverlapping();
// Edits 24-25 — expire ended Premium subscriptions + expiry reminders.
Schedule::command('subscriptions:sweep')->hourly();
