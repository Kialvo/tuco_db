<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared wallets: several people from one agency spending one balance
 * (Fabrizio, 2026-09-09).
 *
 * BRAND-NEW TABLES ONLY. `companies` already exists but is SHARED with the
 * live Menford CRM, and altering a shared table can break an app whose code is
 * not even in this repo. Nothing here touches it, or `users`.
 *
 * Membership is a separate table rather than a column on `users` for the same
 * reason: `users` is live auth for 511 accounts, and a join table gives the
 * same result without altering it. The UNIQUE on user_id is what enforces
 * Fabrizio's model of one team per person.
 *
 * Joining is by INVITATION ONLY. Nobody joins by typing a company name — if
 * they could, anyone could type "Acme Agency" at sign-up and spend Acme's
 * balance. The owner invites by email; the token in the invitation is the
 * proof that the owner initiated it.
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --force --path=database/migrations/2026_09_10_000003_create_marketplace_teams_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_teams', function (Blueprint $table) {
            $table->id();

            // What the customer calls themselves. Free text on purpose: it is
            // a label, never a way to find or join a team.
            $table->string('name');

            // The one account that may invite, remove and hand over ownership.
            // Restricted rather than cascaded: deleting the owner must not
            // silently take the team's wallet with it.
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->index('owner_user_id');
        });

        Schema::create('marketplace_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('marketplace_teams')->cascadeOnDelete();

            // UNIQUE: one team per person. Someone working for two agencies
            // needs two logins, which is the honest answer — a shared balance
            // has to have exactly one owner deciding who spends it.
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->index('team_id');
        });

        Schema::create('marketplace_team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('marketplace_teams')->cascadeOnDelete();

            // Stored lower-cased by the model. The invitation is bound to this
            // address, so accepting with a different account is refused.
            $table->string('email');

            // The proof the owner initiated this. Unique and unguessable;
            // without it an invitation is just an email address someone typed.
            $table->string('token', 64)->unique();

            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();

            // Invitations expire so a forwarded email cannot be redeemed a year
            // later by whoever ends up holding it.
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // "the open invitations for this address" is the only lookup.
            $table->index(['email', 'accepted_at']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_team_invitations');
        Schema::dropIfExists('marketplace_team_members');
        Schema::dropIfExists('marketplace_teams');
    }
};
