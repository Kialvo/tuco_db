CREATE TRIGGER `new_entries_after_insert` AFTER INSERT ON `new_entries` FOR EACH ROW
BEGIN
    /* we log ONLY when the row was originally entered as USD */
    IF NEW.currency_code = 'USD' THEN
        INSERT INTO new_entries_conversion_log      -- ⬅ only the two columns you need
                (new_entry_id, last_used_rate)
        VALUES  (NEW.id,   (
                    SELECT setting_value
                      FROM app_settings
                     WHERE setting_name = 'usd_eur_rate'
                     LIMIT 1
                ));
    END IF;
END
