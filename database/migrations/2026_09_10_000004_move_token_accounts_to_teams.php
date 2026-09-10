<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves wallet ownership from the individual to the team.
 *
 * The balance belongs to the company, so several people from one agency can
 * spend it (Fabrizio, 2026-09-09).
 *
 * ⚠️ THE ONLY MIGRATION IN THIS FEATURE THAT TOUCHES EXISTING DATA. Everything
 * else has been additive-nullable. Run it on its own, read the --pretend output
 * carefully, and check the row counts afterwards.
 *
 * What it does, in order:
 *   1. adds token_accounts.team_id, nullable
 *   2. for every existing token account, creates a team of one owned by that
 *      account's user, and points the account at it
 *   3. leaves user_id in place, untouched
 *
 * user_id is deliberately NOT dropped. It costs nothing to keep, it is the
 * audit trail of who the wallet originally belonged to, and dropping a column
 * on live money to save four bytes is not a trade worth making. The code reads
 * team_id from here on; nothing reads user_id.
 *
 * Reversible: down() clears team_id and removes the column. The teams created
 * by the backfill are left alone — deleting them would take their invitations
 * and members with them.
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --force --path=database/migrations/2026_09_10_000004_move_token_accounts_to_teams.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_accounts', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('user_id')
                ->constrained('marketplace_teams')->nullOnDelete();
        });

        $this->backfillTeams();
    }

    /**
     * Give every existing wallet a team of one.
     *
     * Chunked and idempotent: an account that already has a team is skipped,
     * so a re-run after a partial failure resumes rather than duplicating.
     */
    private function backfillTeams(): void
    {
        DB::table('token_accounts')
            ->whereNull('team_id')
            ->orderBy('id')
            ->chunkById(100, function ($accounts) {
                foreach ($accounts as $account) {
                    $user = DB::table('users')->where('id', $account->user_id)->first();

                    if (! $user) {
                        // Orphaned wallet. Left alone rather than guessed at:
                        // a balance with no owner is a support question, and
                        // tokens:reconcile will keep reporting it.
                        continue;
                    }

                    $teamId = DB::table('marketplace_teams')->insertGetId([
                        'name' => $user->name ?: 'My account',
                        'owner_user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('marketplace_team_members')->insert([
                        'team_id' => $teamId,
                        'user_id' => $user->id,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('token_accounts')
                        ->where('id', $account->id)
                        ->update(['team_id' => $teamId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('token_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
