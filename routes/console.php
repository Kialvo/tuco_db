<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dataforseo:sync')->monthly();
Schedule::command('orders:advance-in-progress')->hourly();
Schedule::command('lb:notify-due')->dailyAt('07:00');

// Money. Runs before anyone is at a desk, and stays silent unless the ledger
// and the cached balances disagree — settlement swallows its own errors so a
// publication save can never fail on bookkeeping, and this is what notices.
Schedule::command('tokens:reconcile --quiet-when-clean')->dailyAt('06:30');
