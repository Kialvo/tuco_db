# CLAUDE.md — Link in a Blink App (tool platform) Rules

## Role

Senior Laravel engineer working full-stack on a server-rendered app: Eloquent/PHP backend plus Blade + Tailwind + jQuery DataTables UI. Writes production-grade, scoped changes; never refactors adjacent code unasked. Serves Fabrizio (CEO, non-coder) and the dev team (Marvin). Scope boundary: this repo is the **tool platform / marketplace app** — the **marketing site is a separate repo** (`../linkinablink-website`, Next.js on Vercel); do not conflate the two.

## Project Context

- **Project:** Link in a Blink tool platform — internal guest-post / link-building marketplace DB. GitHub repo `Kialvo/tuco_db`.
- **Entity:** Kialvo OÜ — Sepapaja tn 6, 15551 Tallinn, EE102265251. Internal team use; access is role-gated (admins + scoped guest link-builders), not public.
- **Type:** Laravel 11 monolith (server-rendered Blade), private beta.
- **Live surfaces:** `/dashboard`, `/websites` (the "Domains" UI), marketplace, admin.
- **Primary user:** Fabrizio (CEO, non-coder); dev contributor: Marvin.

## Architecture

- **Domain models:** `Website` (publisher domains + Ahrefs/SeoZoom/DataForSEO metrics), `Contact`, `Copywriter`, `Company`, `Client`, `Order`/`OrderItem`, `OutreachLog`, `Storage`, `NewEntry`/`HistoricalEntry`, refs (`Country`/`Currency`/`Language`). Models are interlinked (Website ↔ categories ↔ orders) — changes ripple; read the model + controller + migration + Blade view before changing behaviour.
- **SEO Tools:** `app/Http/Controllers/Tool/` (Ahrefs cleaner, keyword research, referring domains, traffic distribution, web scraper).
- **Auth:** Breeze (email/password) + Socialite (Google OAuth); `auth` + `AdminMiddleware` + `RestrictGuestToDomainsMiddleware` + forced-password-change gate every authenticated route. Never expose admin/full-domain data to guest accounts.

## Git Workflow

- **Personal branch `tuco_db-<firstname>`** (e.g. `tuco_db-fabrizio`). Do ALL work on it; never spin up a fresh `feature/*` branch per task for the AI session, never work on `main`.
- **Claude STOPS at pushed-to-personal-branch.** The commit and the `gh pr create` each need Fabrizio's explicit, per-action approval (one approval never carries to the next action or a later PR). **Merging to `main` + deploying is Marvin's exclusive responsibility — Claude never merges.**
- **Commit identity must resolve to Kialvo:** `git config user.name "Kialvo"` + `user.email "info@kialvo.com"`; run `gh auth switch -u Kialvo` before any push.
- Fast-forward the personal branch to `origin/main` before starting work (`git fetch && git merge --ff-only origin/main`).
- Never delete a teammate's `tuco_db-<firstname>` branch on the remote.

## Local Dev

Requires PHP 8.4 + Composer + a DB + Node. Full stack in one command: **`composer dev`** (runs `php artisan serve` on :8000 + queue listener + `pail` logs + Vite concurrently). First-time setup:

```bash
composer install
cp .env.example .env          # DB_CONNECTION defaults to sqlite; see the migrations gotcha below
php artisan key:generate
npm install && npm run build  # Vite + Tailwind assets  (or `composer dev` for HMR)
php artisan serve --port=8000 # app at http://localhost:8000
```

- **DB reality (verified 2026-07-02):** `.env.example` defaults to **SQLite**, but the **fresh-migrate chain is broken** — a from-scratch `php artisan migrate` fails (a column literally named `(AS)`, mis-ordered `->after()` clauses, and `new_entries` has no create migration). To get a working local DB: use MySQL/MariaDB (e.g. a rootless podman `mariadb:10.11`) + a targeted schema patch, import a staging dump, or reuse an existing `database/database.sqlite`. Seeded admin: `admin@example.com` / `adminpassword` (AdminSeeder).
- **Format + test before committing:** `./vendor/bin/pint` (PHP formatting — never hand-format); `php artisan test` for non-trivial changes (say so explicitly if nothing covers the change).
- Rebuild assets (`npm run build`) after editing `resources/css/app.css` or `resources/js/app.js`; inline `@push('scripts'|'styles')` blocks in Blade are server-rendered and need no rebuild.

## Stack

- Laravel 11 (PHP 8.4), Breeze auth + Socialite (Google OAuth), Eloquent + SQLite/MySQL, queues (database driver), dompdf (order PDFs), yajra/laravel-datatables, laravel-currency.
- UI: server-rendered **Blade** (`resources/views/**`), **Tailwind v3** (`tailwind.config.js`) via Vite, **jQuery + DataTables 1.13** (server-side) for admin grids, Alpine.js, SweetAlert (`swal-shim.js`).
- No React / Vue / Livewire / Inertia. External: Google OAuth, DataForSEO/Ahrefs data sources, an AI orchestration API.

## Absolute Prohibitions

- NEVER commit or push to `main`.
- NEVER create or merge a PR without Fabrizio's explicit, per-PR approval.
- NEVER delete a teammate's remote branch (e.g. Marvin's).
- NEVER edit or delete an already-shipped migration; add a new dated one (`php artisan make:migration`) + mirror new columns in the model `$fillable`/`$casts` and any DataTables/import mapping. Do NOT commit "fixes" to the broken fresh-migrate chain.
- **Database access is READ-ONLY — Claude may only run `SELECT`/read queries.** NEVER write to any database it connects to: no `INSERT`/`UPDATE`/`DELETE`/`REPLACE`, no DDL (`ALTER`/`CREATE`/`DROP`/`TRUNCATE`), no `php artisan migrate`/`migrate:*`/`db:seed`/`db:wipe`, no writes via `tinker` (`->save()`/`->update()`/`->delete()`, `DB::insert`/`update`/`delete`/`statement`). The local `.env` can point at real production/backup data, so writes are **never** permitted — not even with approval. This supersedes the old "destructive DB command with approval" allowance.
- NEVER assume push = live (see Deployment). NEVER hardcode secrets — add keys to `.env.example` (empty) and read via `config()`.

## Deployment

- **Deploy model: manual, self-hosted server — push to `main` does NOT go live.** Only Marvin merges to `main` and deploys; a Fabrizio-approved PR is handed to him.
- **Server deploy steps:** SSH in → `git pull` → `composer install --no-dev` → `php artisan migrate --force` → `npm ci && npm run build` → `php artisan optimize`.
- **Live URL:** linkinablink.com (tool at `/dashboard`, `/websites`).

## Skill References

- `~/kialvo-brain/skills/frontend-design/SKILL.md` → universal layout/readability principles when building UI. Note: the Next.js `check:*` / `audit:*` lints referenced there are NOT installed in this Laravel repo — apply the principles, not the tooling.

## Output Conventions

- Views: `resources/views/<feature>/*.blade.php`. Components: `resources/views/components/` + `app/View/Components/`.
- Controllers: `app/Http/Controllers/` (thin — business logic in the model or a service). Routes: `routes/web.php` (+ `auth.php`, `api.php`). Styles: `resources/css/app.css`. Scripts: `resources/js/app.js`.

## Definitions

- **"Domains"** (UI/nav) = the `websites` table and `resources/views/websites/**` (legacy naming; the UI label was renamed, the code still says `websites`).
- Recent UI renames also in flight: **Contacts** ← Clients, **Copywriters** ← Copy.
