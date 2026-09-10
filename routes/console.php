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

// The two marketplace deadlines. Weekdays only: the clocks are counted in
// working days, so nothing can newly expire over a weekend — a Friday-evening
// deadline is picked up Monday morning, which is when someone is here to deal
// with the fallout. No-ops entirely unless
// linkbuilding.marketplace_deadlines.enabled is switched on.
Schedule::command('marketplace:advance-overdue')->weekdays()->at('08:00');
