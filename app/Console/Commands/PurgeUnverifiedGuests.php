<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bot-account cleanup — REPORT-ONLY by default. Nothing is ever deleted
 * unless --apply is passed explicitly (Marvin runs that himself after
 * reviewing the report). Not scheduled anywhere, by design.
 *
 * A user is a purge candidate only when ALL of these hold:
 *   role = guest · email never verified · no Google account
 *   · older than --days (default 7) · zero orders · zero favorites
 * Verifying an account (email_verified_at set, even manually) permanently
 * protects it from this command.
 */
class PurgeUnverifiedGuests extends Command
{
    protected $signature = 'users:purge-unverified-guests
                            {--apply : Actually delete (default is report-only)}
                            {--days=7 : Only consider accounts older than this many days}';

    protected $description = 'Report (default) or delete unverified, inactive guest accounts (bot signups)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $days  = max(1, (int) $this->option('days'));

        $candidates = DB::table('users')
            ->where('role', 'guest')
            ->whereNull('email_verified_at')
            ->whereNull('google_id')
            ->where('created_at', '<', now()->subDays($days))
            ->whereNotIn('id', DB::table('orders')->whereNotNull('user_id')->select('user_id'))
            ->whereNotIn('id', DB::table('user_favorite_domains')->select('user_id'))
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'created_at']);

        if ($candidates->isEmpty()) {
            $this->info('No purge candidates found.');

            return self::SUCCESS;
        }

        $this->info(($apply ? '' : '[REPORT ONLY] ') . $candidates->count()
            . " unverified guest account(s) older than {$days} day(s), zero orders/favorites:");
        foreach ($candidates->take(15) as $u) {
            $this->line(sprintf('  #%-6d %-20s %-45s %s', $u->id, mb_substr($u->name, 0, 20), $u->email, $u->created_at));
        }
        if ($candidates->count() > 15) {
            $this->line('  … and ' . ($candidates->count() - 15) . ' more (full list in the audit file)');
        }

        // Full list to an audit file either way, so Marvin can review every row.
        $file = 'guest_purge_' . ($apply ? 'applied_' : 'report_') . now()->format('Ymd_His') . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put($file, $candidates->toJson(JSON_PRETTY_PRINT));
        $this->info("Full list written to storage/app/{$file}");

        if (! $apply) {
            $this->warn('Nothing deleted. Review the list, then run with --apply to delete.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Delete these ' . $candidates->count() . ' account(s) permanently?')) {
            $this->info('Aborted — nothing deleted.');

            return self::SUCCESS;
        }

        $deleted = DB::table('users')->whereIn('id', $candidates->pluck('id'))->delete();
        $this->info("{$deleted} account(s) deleted.");

        return self::SUCCESS;
    }
}
