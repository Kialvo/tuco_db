# Database triggers

These six triggers exist **only inside the database**. They were written by hand
in July/August 2025, never through a migration, so until now there was no record
in the repo of what they do or when they changed. Anyone reading the Laravel code
alone would have no idea that the database recalculates columns behind the
application's back.

The files here are a **reference copy, not a migration.** Nothing runs them.
They document the state that should be live after
`2026_09_01_000004_remove_profit_from_currency_triggers` has been applied.

## What they do

| Trigger | Table | Purpose |
|---|---|---|
| `*_before_insert` | websites, new_entries | On `currency_code = 'USD'`, converts the six price columns and `automatic_evaluation` from USD to EUR using `app_settings.usd_eur_rate` |
| `*_before_update` | websites, new_entries | On USD rows, re-derives the same six price columns from their `original_*` twin × the current rate, so a rate change flows through on the next save |
| `*_after_insert` | websites, new_entries | Records the rate a USD row was created at, into `*_conversion_log`. `conversion:daily` uses it to re-scale later |

**They no longer touch `profit`.** That column is owned solely by
`App\Services\DomainProfitCalculator`. See the migration above for why.

Note the asymmetry, which is intentional and pre-existing: `banner_price` and
`sitewide_link_price` are converted on insert and re-derived on update, but
`automatic_evaluation` is converted on insert only — the update clause for it is
commented out in `websites_before_update`.

## Re-dumping them

To check the live definitions still match these files (read-only):

```sql
SELECT TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION, ACTION_STATEMENT
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE();
```

If they have drifted, someone changed a trigger by hand again. Update these
files in the same PR as whatever change caused it.

## Changing one

Never edit a trigger directly in the database. Add a dated migration that does
`DROP TRIGGER` + `CREATE TRIGGER`, with a `down()` that restores the previous
definition verbatim, and update the matching file here. MySQL has no
`ALTER TRIGGER` and DDL is not transactional, so there is always a brief moment
where the trigger does not exist: run it outside working hours, after a backup.
