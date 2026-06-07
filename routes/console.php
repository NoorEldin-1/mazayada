<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auctions:activate')->everyMinute();
Schedule::command('auctions:close')->everyMinute();
// §4 step 8 — settle deposits (refund losers / forfeit defaulters) after the
// final-payment deadline; §10.1 — remind winners before the deadline.
Schedule::command('auctions:settle-deposits')->hourly();
Schedule::command('auctions:remind-final-payment')->daily();
Schedule::command('kyc:suspend-stale')->daily();
