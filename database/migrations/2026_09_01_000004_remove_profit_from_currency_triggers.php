<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stop the database from writing `websites.profit` / `new_entries.profit`.
 *
 * Until now profit had two owners. PHP calculated it, and for USD rows these
 * four triggers calculated it again — the two BEFORE UPDATE triggers ignoring
 * PHP entirely and recomputing from `original_publisher_price * rate`, the two
 * BEFORE INSERT triggers multiplying PHP's figure by the rate. The two
 * disagreed with each other and with PHP, so the same domain could show a
 * different Profit depending on how it was last saved.
 *
 * It also made the Link Builder € cost impossible to subtract correctly: the
 * amount is always entered in euros, so a trigger that rescales the whole
 * figure by the USD->EUR rate silently shrinks it.
 *
 * The fix is a deletion, not a rewrite. Each trigger loses exactly one clause:
 *
 *   BEFORE INSERT:  SET NEW.profit = NEW.profit * daily_rate;
 *   BEFORE UPDATE:  , NEW.profit = IF(NEW.currency_code = 'USD', ...)
 *
 * Everything else is byte-for-byte what was running in production on
 * 2026-09-01: all six price conversions, the USD guard, automatic_evaluation,
 * and the commented-out block at the bottom of websites_before_update. The two
 * AFTER INSERT triggers that log the rate into *_conversion_log are not touched
 * at all. FX conversion of the price columns still belongs to the database;
 * only `profit` moves to PHP (App\Services\DomainProfitCalculator).
 *
 * MySQL has no ALTER TRIGGER and DDL is not transactional, so this is a
 * DROP + CREATE per trigger. For a fraction of a second each trigger does not
 * exist — a USD row saved in exactly that instant would keep unconverted
 * prices. Run it outside working hours, after a backup.
 *
 * DEFINER is deliberately omitted so the trigger is created as whoever runs
 * the migration; production connects as `doadmin`, the same definer the
 * existing triggers carry.
 *
 * down() restores the exact definitions captured from production before the
 * change, so a rollback is a true undo.
 *
 * NOTE: no row is rewritten. Nothing here contains an UPDATE — every existing
 * Profit value stays as it is until that row is next saved by the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->replace([
            'websites_before_insert' => <<<'SQL'
CREATE TRIGGER `websites_before_insert` BEFORE INSERT ON `websites` FOR EACH ROW
BEGIN
    /* fetch today’s USD→EUR rate once - cheap, keeps trigger readable */
    DECLARE daily_rate DECIMAL(10,6);

    SELECT setting_value
      INTO daily_rate
      FROM app_settings
     WHERE setting_name = 'usd_eur_rate'
     LIMIT 1;

    /* convert only when the user selected USD */
    IF NEW.currency_code = 'USD' THEN
        SET NEW.publisher_price       = NEW.publisher_price      * daily_rate;
        SET NEW.link_insertion_price  = NEW.link_insertion_price * daily_rate;
        SET NEW.no_follow_price       = NEW.no_follow_price      * daily_rate;
        SET NEW.special_topic_price   = NEW.special_topic_price  * daily_rate;
        SET NEW.banner_price          = NEW.banner_price         * daily_rate;
        SET NEW.sitewide_link_price   = NEW.sitewide_link_price  * daily_rate;
        SET NEW.automatic_evaluation  = NEW.automatic_evaluation * daily_rate;
    END IF;
END
SQL,
            'websites_before_update' => <<<'SQL'
CREATE TRIGGER `websites_before_update` BEFORE UPDATE ON `websites` FOR EACH ROW
SET
    /* apply only on USD rows – otherwise leave values unchanged */
    NEW.publisher_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_publisher_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.publisher_price),

    NEW.link_insertion_price  = IF(NEW.currency_code = 'USD',
                                   NEW.original_link_insertion_price * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.link_insertion_price),

    NEW.no_follow_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_no_follow_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.no_follow_price),

    NEW.special_topic_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_special_topic_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.special_topic_price),

    NEW.banner_price          = IF(NEW.currency_code = 'USD',
                                   NEW.original_banner_price         * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.banner_price),

    NEW.sitewide_link_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_sitewide_link_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.sitewide_link_price)

/*   , NEW.automatic_evaluation = IF(NEW.currency_code = 'USD',
                                     NEW.automatic_evaluation *
                                     (SELECT setting_value
                                      FROM app_settings
                                      WHERE setting_name = 'usd_eur_rate'
                                      LIMIT 1),
                                     NEW.automatic_evaluation)  */
/*  ↑ uncomment if you really want to scale that field as well       */
SQL,
            'new_entries_before_insert' => <<<'SQL'
CREATE TRIGGER `new_entries_before_insert` BEFORE INSERT ON `new_entries` FOR EACH ROW
BEGIN
    /* fetch today’s USD→EUR rate once - cheap, keeps trigger readable */
    DECLARE daily_rate DECIMAL(10,6);

    SELECT setting_value
      INTO daily_rate
      FROM app_settings
     WHERE setting_name = 'usd_eur_rate'
     LIMIT 1;

    /* convert only when the user selected USD */
    IF NEW.currency_code = 'USD' THEN
        SET NEW.publisher_price       = NEW.publisher_price      * daily_rate;
        SET NEW.link_insertion_price  = NEW.link_insertion_price * daily_rate;
        SET NEW.no_follow_price       = NEW.no_follow_price      * daily_rate;
        SET NEW.special_topic_price   = NEW.special_topic_price  * daily_rate;
        SET NEW.banner_price          = NEW.banner_price         * daily_rate;
        SET NEW.sitewide_link_price   = NEW.sitewide_link_price  * daily_rate;
        SET NEW.automatic_evaluation  = NEW.automatic_evaluation * daily_rate;
    END IF;
END
SQL,
            'new_entries_before_update' => <<<'SQL'
CREATE TRIGGER `new_entries_before_update` BEFORE UPDATE ON `new_entries` FOR EACH ROW
SET
    /* apply only on USD rows – otherwise leave values unchanged */
    NEW.publisher_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_publisher_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.publisher_price),

    NEW.link_insertion_price  = IF(NEW.currency_code = 'USD',
                                   NEW.original_link_insertion_price * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.link_insertion_price),

    NEW.no_follow_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_no_follow_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.no_follow_price),

    NEW.special_topic_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_special_topic_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.special_topic_price),

    NEW.banner_price          = IF(NEW.currency_code = 'USD',
                                   NEW.original_banner_price         * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.banner_price),

    NEW.sitewide_link_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_sitewide_link_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.sitewide_link_price)
SQL,
        ]);
    }

    public function down(): void
    {
        $this->replace([
            'websites_before_insert' => <<<'SQL'
CREATE TRIGGER `websites_before_insert` BEFORE INSERT ON `websites` FOR EACH ROW
BEGIN
    /* fetch today’s USD→EUR rate once - cheap, keeps trigger readable */
    DECLARE daily_rate DECIMAL(10,6);

    SELECT setting_value
      INTO daily_rate
      FROM app_settings
     WHERE setting_name = 'usd_eur_rate'
     LIMIT 1;

    /* convert only when the user selected USD */
    IF NEW.currency_code = 'USD' THEN
        SET NEW.publisher_price       = NEW.publisher_price      * daily_rate;
        SET NEW.link_insertion_price  = NEW.link_insertion_price * daily_rate;
        SET NEW.no_follow_price       = NEW.no_follow_price      * daily_rate;
        SET NEW.special_topic_price   = NEW.special_topic_price  * daily_rate;
        SET NEW.banner_price          = NEW.banner_price         * daily_rate;
        SET NEW.sitewide_link_price   = NEW.sitewide_link_price  * daily_rate;
        SET NEW.profit                = NEW.profit               * daily_rate;
        SET NEW.automatic_evaluation  = NEW.automatic_evaluation * daily_rate;
    END IF;
END
SQL,
            'websites_before_update' => <<<'SQL'
CREATE TRIGGER `websites_before_update` BEFORE UPDATE ON `websites` FOR EACH ROW
SET
    /* apply only on USD rows – otherwise leave values unchanged */
    NEW.publisher_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_publisher_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.publisher_price),

    NEW.link_insertion_price  = IF(NEW.currency_code = 'USD',
                                   NEW.original_link_insertion_price * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.link_insertion_price),

    NEW.no_follow_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_no_follow_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.no_follow_price),

    NEW.special_topic_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_special_topic_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.special_topic_price),

    NEW.banner_price          = IF(NEW.currency_code = 'USD',
                                   NEW.original_banner_price         * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.banner_price),

    NEW.sitewide_link_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_sitewide_link_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.sitewide_link_price),

    /* helper columns: always keep them coherent on USD rows */
    NEW.profit                = IF(NEW.currency_code = 'USD',
                                   NEW.kialvo_evaluation -
                                   (NEW.original_publisher_price * (SELECT setting_value
                                                                    FROM app_settings
                                                                    WHERE setting_name = 'usd_eur_rate'
                                                                    LIMIT 1)),
                                   NEW.profit)

/*   , NEW.automatic_evaluation = IF(NEW.currency_code = 'USD',
                                     NEW.automatic_evaluation *
                                     (SELECT setting_value
                                      FROM app_settings
                                      WHERE setting_name = 'usd_eur_rate'
                                      LIMIT 1),
                                     NEW.automatic_evaluation)  */
/*  ↑ uncomment if you really want to scale that field as well       */
SQL,
            'new_entries_before_insert' => <<<'SQL'
CREATE TRIGGER `new_entries_before_insert` BEFORE INSERT ON `new_entries` FOR EACH ROW
BEGIN
    /* fetch today’s USD→EUR rate once - cheap, keeps trigger readable */
    DECLARE daily_rate DECIMAL(10,6);

    SELECT setting_value
      INTO daily_rate
      FROM app_settings
     WHERE setting_name = 'usd_eur_rate'
     LIMIT 1;

    /* convert only when the user selected USD */
    IF NEW.currency_code = 'USD' THEN
        SET NEW.publisher_price       = NEW.publisher_price      * daily_rate;
        SET NEW.link_insertion_price  = NEW.link_insertion_price * daily_rate;
        SET NEW.no_follow_price       = NEW.no_follow_price      * daily_rate;
        SET NEW.special_topic_price   = NEW.special_topic_price  * daily_rate;
        SET NEW.banner_price          = NEW.banner_price         * daily_rate;
        SET NEW.sitewide_link_price   = NEW.sitewide_link_price  * daily_rate;
        SET NEW.profit                = NEW.profit               * daily_rate;
        SET NEW.automatic_evaluation  = NEW.automatic_evaluation * daily_rate;
    END IF;
END
SQL,
            'new_entries_before_update' => <<<'SQL'
CREATE TRIGGER `new_entries_before_update` BEFORE UPDATE ON `new_entries` FOR EACH ROW
SET
    /* apply only on USD rows – otherwise leave values unchanged */
    NEW.publisher_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_publisher_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.publisher_price),

    NEW.link_insertion_price  = IF(NEW.currency_code = 'USD',
                                   NEW.original_link_insertion_price * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.link_insertion_price),

    NEW.no_follow_price       = IF(NEW.currency_code = 'USD',
                                   NEW.original_no_follow_price      * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.no_follow_price),

    NEW.special_topic_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_special_topic_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.special_topic_price),

    NEW.banner_price          = IF(NEW.currency_code = 'USD',
                                   NEW.original_banner_price         * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.banner_price),

    NEW.sitewide_link_price   = IF(NEW.currency_code = 'USD',
                                   NEW.original_sitewide_link_price  * (SELECT setting_value
                                                                         FROM app_settings
                                                                         WHERE setting_name = 'usd_eur_rate'
                                                                         LIMIT 1),
                                   NEW.sitewide_link_price),

    /* helper columns: always keep them coherent on USD rows */
    NEW.profit                = IF(NEW.currency_code = 'USD',
                                   NEW.kialvo_evaluation -
                                   (NEW.original_publisher_price * (SELECT setting_value
                                                                    FROM app_settings
                                                                    WHERE setting_name = 'usd_eur_rate'
                                                                    LIMIT 1)), NEW.profit)
SQL,
        ]);
    }

    /**
     * @param  array<string, string>  $definitions  trigger name => CREATE statement
     */
    private function replace(array $definitions): void
    {
        foreach ($definitions as $name => $sql) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$name}`");
            DB::unprepared($sql);
        }
    }
};
