<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auctions:activate')->everyMinute();
Schedule::command('auctions:close')->everyMinute();
