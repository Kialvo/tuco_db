# CLAUDE.md — Link in a Blink App (tool platform) Rules

## Role

Senior Laravel engineer working full-stack on a server-rendered app: Eloquent/PHP backend plus Blade + Tailwind + jQuery DataTables UI. Writes production-grade, scoped changes; never refactors adjacent code unasked. Serves Fabrizio (CEO, non-coder) and the dev team (Marvin). Scope boundary: this repo is the **tool platform / marketplace app** — the **marketing site is a separate repo** (`../linkinablink-website`, Next.js on Vercel); do not conflate the two.

## Project Context

- **Project:** Link in a Blink tool platform — publisher/domain catalog, orders, admin. GitHub repo `Kialvo/tuco_db`.
- **Entity:** Kialvo OÜ — Sepapaja tn 6, 15551 Tallinn, EE102265251.
- **Type:** Laravel 11 monolith (server-rendered Blade), private beta.
- **Live surfaces:** `/dashboard`, `/websites` (the "Domains" UI), marketplace, admin.
- **Primary user:** Fabrizio (CEO, non-coder); dev contributor: Marvin.

## Git Workflow

- **Personal branch `tuco_db-<firstname>`** (e.g. `tuco_db-fabrizio`). Do ALL work on it; never spin up a fresh `feature/*` branch per task for the AI session, never work on `main`.
- **Claude STOPS at pushed-to-personal-branch.** The commit and the `gh pr create` each need Fabrizio's explicit, per-action approval (one approval never carries to the next action or a later PR). **Merging to `main` + deploying is Marvin's exclusive responsibility — Claude never merges.**
- **Commit identity must resolve to Kialvo:** `git config user.name "Kialvo"` + `user.email "info@kialvo.com"`; run `gh auth switch -u Kialvo` before any push.
- Fast-forward the personal branch to `origin/main` before starting work (`git fetch && git merge --ff-only origin/main`).

## Local Dev

Requires PHP 8.4 + Composer + a **MySQL-family DB** (not SQLite) + Node. Reference setup (matches what's already on this machine):

```bash
composer install
cp .env.example .env          # set DB_* to a local MySQL/MariaDB
php artisan key:generate
# schema: see "Migrations gotcha" — migrate:fresh does NOT replay from scratch
npm install && npm run build  # Vite + Tailwind assets
php artisan serve --port=8000 # app at http://localhost:8000  (+ `npm run dev` for HMR)
```

- **DB used locally:** rootless podman container `lib-mariadb` (mariadb:10.11, root/root, db `linkinablink`, port 3306).
- **Seeded admin login:** `admin@example.com` / `adminpassword` (AdminSeeder).
- **Migrations gotcha:** `migrate:fresh` fails from scratch — mis-ordered `->after()` clauses, a column literally named `(AS)`, and `new_entries` has no create migration. For a local DB, patch throwaway + revert, or import a schema dump from staging. Do NOT commit migration "fixes" (see Prohibitions).
- Rebuild assets (`npm run build`) after editing `resources/css/app.css` or `resources/js/app.js`; inline `@push('scripts'|'styles')` blocks in Blade are server-rendered and need no rebuild.

## Stack

- Laravel 11 (PHP 8.4), Breeze auth + Socialite (Google OAuth), Eloquent + MySQL/MariaDB.
- UI: server-rendered **Blade** (`resources/views/**`), **Tailwind v3** (`tailwind.config.js`) via Vite, **jQuery + DataTables 1.13** (server-side via `yajra/laravel-datatables`) for admin grids, SweetAlert (`swal-shim.js`), dompdf for PDF exports.
- No React / Vue / Livewire / Inertia.

## Absolute Prohibitions

- NEVER commit or push to `main`.
- NEVER create or merge a PR without Fabrizio's explicit, per-PR approval.
- NEVER delete a teammate's remote branch (e.g. Marvin's).
- NEVER edit migration files to "fix" the broken fresh-migrate chain and commit them — it rewrites shared behaviour for a problem that only affects from-scratch replay.
- NEVER assume push = live — the deploy model is unconfirmed (see Deployment).
- NEVER hardcode secrets or paste them into chat; use `.env`.

## Deployment

- **Only Marvin merges to `main` and deploys.** Claude/others never merge; a Fabrizio-approved PR is handed to Marvin to merge + ship. Never assume push = live.
- **Deploy mechanism:** TBD — **NOT Vercel** (Laravel/PHP); no CI / Forge / Envoyer config in-repo. Marvin owns the deploy path.
- **Live URL:** linkinablink.com (tool at `/dashboard`, `/websites`).

## Skill References

- `~/kialvo-brain/skills/frontend-design/SKILL.md` → universal layout/readability principles when building UI. Note: the Next.js `check:*` / `audit:*` lints referenced there are NOT installed in this Laravel repo — apply the principles, not the tooling.

## Output Conventions

- Views: `resources/views/<feature>/*.blade.php`. Components: `resources/views/components/` + `app/View/Components/`.
- Controllers: `app/Http/Controllers/`. Routes: `routes/web.php` (+ `auth.php`, `api.php`).
- Styles: `resources/css/app.css`. Scripts: `resources/js/app.js`.

## Definitions

- **"Domains"** (UI/nav) = the `websites` table and `resources/views/websites/**` (legacy naming; the UI label was renamed, the code still says `websites`).
- Recent UI renames also in flight: **Contacts** ← Clients, **Copywriters** ← Copy.
