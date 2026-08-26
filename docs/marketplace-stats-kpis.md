# Marketplace Stats — Approved KPI Specification

**Status:** Approved by Fabrizio on 2026-08-26 (KPI workshop session). This document is the agreed scope for the new "Marketplace Stats" sidebar group. No code exists yet — controllers, routes, and Blade views are a later session, built against this spec.

**Relation to the existing Stats area:** the four live Stats pages (Financial, Campaigns, Production, Publisher) all measure the Link-Building *service*. Nothing below duplicates them — this spec covers only the marketplace (guest buyers, token wallet, self-serve orders).

---

## 1. Decisions this spec rests on

Agreed with Fabrizio on 2026-08-26:

1. **Primary lens: Growth first.** Acquisition/activation KPIs rank first; revenue KPIs second. The marketplace is in private beta — the leading question is "is anyone using it", not yet "is it profitable".
2. **Boundary: guests only.** Marketplace activity = activity of users with `role = 'guest'`.
3. **Sidebar: two groups, Sales absorbed.** The four live pages regroup under a **Link Building** heading; a **Marketplace** heading is added; the `Sales Stats` (soon) placeholder is retired and its intent absorbed here.
4. **Funnel scope: v1 from existing data only.** No new event instrumentation now. Top-of-funnel KPIs are specced below but tagged `needs-instrumentation` and parked.

## 2. Boundary and definitions

- **Roles:** the codebase references four roles — `admin`, `editor`, `publisher`, `guest`. Only `guest` counts as marketplace activity. **Every KPI query joins `users` and filters `role = 'guest'`** — never assume the `orders` table contains only guest orders (`OrderController` has no internal role check; gating is at route level, and admin/test orders do exist).
- **Buyer:** a guest with at least one order whose `submitted_at` is not null. Submission is the counting event; later cancellation is shown as a secondary line, not silently excluded.
- **Cart:** `orders.status = 'draft'` IS the open cart — one per user at a time. No separate cart table exists.
- **Token peg:** 1 token = 1 EUR, permanently (`config/tokens.php`). Token counts are therefore EUR-denominated by construction; cash amounts (`amount_minor`) are per-currency (EUR/USD) and must never be summed across currencies.

## 3. Sidebar re-grouping spec

| Group | Items | Notes |
|-------|-------|-------|
| **Link Building** | Financial Stats · Campaigns Stats · Production Stats · Publisher Stats | The four pages live today in `stats-sidebar.blade.php`, unchanged — only regrouped under this heading |
| **Marketplace** | Growth · Revenue & Tokens | New pages, built from the KPI tables below |
| *(removed)* | Sales Stats (soon) | Placeholder retired; its sales/leads intent is covered by the Marketplace group |

## 4. KPI list — Group 1: Marketplace · Growth

Sidebar item **Growth**, ranked first. All queries filter to `role = 'guest'`.

| # | KPI | Definition | Data source | Tag |
|---|-----|-----------|-------------|-----|
| 1 | New guest signups | Count of `users` (role=guest) by `created_at` month | `users` | `computable-today` |
| 2 | Activated buyers | Guests whose FIRST submitted order OR first paid top-up falls in the month | `orders.submitted_at`, `token_purchases.paid_at` | `computable-today` |
| 3 | Signup→first-order conversion | % of a signup cohort submitting their first order within 30 days of signup | `users` + `orders` | `computable-today` |
| 4 | Orders submitted | Orders with `submitted_at` in month; status ≠ cancelled shown as a secondary line | `orders` | `computable-today` |
| 5 | Active buyers (monthly) | Guests with any order submission or token transaction in the month | `orders`, `token_transactions` | `computable-today` |
| 6 | Repeat-buyer rate | % of buyers with ≥2 submitted orders lifetime | `orders` | `computable-today` |
| 7 | Open-cart snapshot + abandonment | Count and age of `draft` orders holding ≥1 item; % idle >7 days | `orders` + `order_items` | `computable-today` |
| 8 | Time to first purchase | Median days from signup to first `submitted_at` | `users` + `orders` | `computable-today` |
| 9 | Favorites → order conversion | Favorites added per month (`created_at` is written on insert); % of favorited websites later appearing in that user's order items | `user_favorite_domains`, `order_items` | `computable-today` |
| 10 | Domain views / searches / view→cart rate | Top-of-funnel engagement — parked until instrumentation is prioritized | none (needs event table) | `needs-instrumentation` |

## 5. KPI list — Group 2: Marketplace · Revenue & Tokens

Sidebar item **Revenue & Tokens**, ranked second. All queries filter to `role = 'guest'`.

| # | KPI | Definition | Data source | Tag |
|---|-----|-----------|-------------|-----|
| 1 | Token revenue | `SUM(amount_minor)` of `status='paid'` purchases by month, **split by currency — never a naive cross-currency sum**; tokens sold shown alongside as the peg-normalized line | `token_purchases` | `computable-today` |
| 2 | Tokens purchased vs spent | Monthly signed ledger sums: `purchase`+`bonus` credits vs `spend` debits — is credit being used or parked? | `token_transactions` | `computable-today` |
| 3 | Outstanding token liability | `SUM(token_accounts.balance_cached)` = prepaid EUR owed to buyers, cross-checked against ledger `SUM(amount)` — `balance_cached` is a cache, the ledger is the truth | `token_accounts` + `token_transactions` | `computable-today` |
| 4 | Package mix | Paid purchases by `package_key` (starter/standard/pro/custom): count + revenue share | `token_purchases` | `computable-today` |
| 5 | Average order value | Mean of `SUM(order_items.unit_price)` per submitted order; split by `article_type` (standard vs sensitive) | `order_items` | `computable-today` |
| 6 | Revenue per buyer (ARPPU) | Paid top-up value ÷ distinct paying guests, monthly, per currency | `token_purchases` | `computable-today` |
| 7 | Payment success rate | `paid` ÷ (`paid` + `failed` + `expired`) purchase attempts | `token_purchases` (+ `payment_events` for diagnosis) | `computable-today` |
| 8 | Refunds & adjustments | Refunded purchases + ledger `refund`/`adjustment` volume per month | `token_purchases`, `token_transactions` | `computable-today` |
| 9 | Bonus tokens granted | Monthly `bonus_tokens` on paid purchases — the cost of the discount policy | `token_purchases` | `computable-today` |
| 10 | Order pipeline health | Current status distribution + median submitted→completed days (completed orders only — see `status_changed_at` note below) | `orders` | `computable-today` |

## 6. Out of scope / deferred

- **All top-of-funnel instrumentation** (event table, page-view/search logging). Growth #10 stays parked until the v1 pages prove the need. No tracking of this kind exists anywhere in the app today; `payment_events` is the only event table.
- **Fulfilment-cost margin per marketplace order.** Publisher cost lives in the Link-Building `storage` world; linking it to marketplace `order_items` is a later modelling question — open item, not a v1 KPI.

## 7. Schema notes for implementers

Verified against migrations and code on 2026-08-26:

- **`orders` has no total column** — order value is always `SUM(order_items.unit_price)` for the order.
- **`order_items` has no quantity column** — one row per website per order (`unique(order_id, website_id)`), quantity is implicitly 1.
- **`token_transactions` is an append-only ledger** — signed integer `amount`, types `purchase|spend|refund|adjustment|bonus|expiry`, `balance_after` snapshot. Never UPDATE/DELETE; corrections are compensating rows.
- **`token_accounts.balance_cached` is a cache** — the ledger is authoritative; a scheduled reconciliation alerts on drift. Liability KPIs must cross-check.
- **`orders.status_changed_at` holds only the LAST transition** (added 2026-05-15). Full time-in-stage history is not derivable; submitted→terminal duration works only for orders currently in a terminal status.
- **`user_favorite_domains` has no migration** — real shape from `FavoriteController.php`: `user_id`, `website_id`, `website_snapshot` (JSON), `created_at`, `updated_at`. Timestamps ARE written, so monthly trends are computable.
- **Access control:** Marketplace stats pages sit behind the same admin gating as the existing stats routes. Never expose them to guest accounts.
- **Production caution:** every configured DB connection in local `.env` hits LIVE production. Stats queries are reads by nature, but during development prefer building against schema knowledge in this doc over ad-hoc production queries.
