<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdvanceOrdersInProgress extends Command
{
    protected $signature   = 'orders:advance-in-progress';
    protected $description = 'Move submitted orders to In Progress after 24 hours';

    public function handle(): int
    {
        $now = now();

        $updated = DB::table('orders')
            ->where('status', Order::STATUS_SUBMITTED)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $now->copy()->subHours(24))
            ->update([
                'status'            => Order::STATUS_IN_PROGRESS,
                'status_changed_at' => $now,
                'updated_at'        => $now,
            ]);

        $this->info("Advanced {$updated} order(s) to In Progress.");

        return self::SUCCESS;
    }
}
