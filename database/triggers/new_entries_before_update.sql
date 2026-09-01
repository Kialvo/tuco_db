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
