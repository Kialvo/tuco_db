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
