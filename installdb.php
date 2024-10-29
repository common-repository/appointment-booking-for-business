<?php
defined('ABSPATH') || die('Access Denied');
function BKFORB_GCAL_g_cal_install () {
	global $wpdb;
	
	$create_foreign_keys = true;

	$pref = $wpdb->prefix;
	$db_option_name = "gcal_db_version";

	$current_ver = intval(get_option($db_option_name, 0));
	$charset_collate = $wpdb->get_charset_collate();
			
	$tables = array(
		"{$pref}g_payment_systems_calendar",
		"{$pref}g_stripe_transactions",
		"{$pref}g_stripe_settings",
		"{$pref}g_payment_transactions",
		"{$pref}g_payment_systems",

		"{$pref}g_calendar_timing",
		"{$pref}g_calendar_inactivity_times",
		"{$pref}g_extra_calendar",
		"{$pref}g_extras_booking",
		"{$pref}g_extras",
		"{$pref}g_booking",
		"{$pref}g_calendars",
		"{$pref}g_customers"
	);

	if ($current_ver < 2) {
		$sqls = array();

		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_booking` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `date` date NOT NULL,
		  `time_from` int(11) NOT NULL,
		  `time_to` int(11) NOT NULL,
		  `customer_id` int(11) NOT NULL,
		  `calendar_id` int(11) NOT NULL,
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `{$pref}fk_calendar` (`calendar_id`),
		  KEY `{$pref}fk_customer` (`customer_id`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_calendars` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `time_from` int(11) NOT NULL DEFAULT '480',
		  `time_to` int(11) NOT NULL DEFAULT '1020',
		  `unavail_time_from` int(11) NOT NULL DEFAULT '0',
		  `unavail_time_to` int(11) NOT NULL DEFAULT '0',
		  `slot_duration` int(11) NOT NULL DEFAULT '60',
		  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `currency` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '$',
		  PRIMARY KEY (`id`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_calendar_inactivity_times` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `calendar_id` int(11) NOT NULL,
		  `time_from` int(11) NOT NULL DEFAULT '0',
		  `time_to` int(11) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `{$pref}fk_cal_time` (`calendar_id`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_calendar_timing` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `calendar_id` int(11) NOT NULL,
		  `status` enum('unavail','avail') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unavail',
		  `date_from` date NOT NULL,
		  `date_to` date NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `{$pref}calendar_id` (`calendar_id`),
		  KEY `{$pref}date_ind` (`date_from`,`date_to`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_customers` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
		  `phone` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
		  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `email` (`email`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_extras` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		  `price` decimal(10,2) DEFAULT '0.00',
		  `sum_op` enum('+','%') CHARACTER SET ascii NOT NULL DEFAULT '+',
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_extras_booking` (
		  `extra_id` int(11) NOT NULL,
		  `booking_id` int(11) NOT NULL,
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  KEY `{$pref}fk_booking` (`booking_id`),
		  KEY `{$pref}fk_extra_id` (`extra_id`)
		) $charset_collate;";


		$sqls[] = "CREATE TABLE IF NOT EXISTS `{$pref}g_extra_calendar` (
		  `calendar_id` int(11) NOT NULL,
		  `extra_id` int(11) NOT NULL,
		  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  UNIQUE KEY `uniq_cal_extra` (`calendar_id`,`extra_id`),
		  KEY `{$pref}fk_calendar_id` (`calendar_id`),
		  KEY `{$pref}fk_ex` (`extra_id`)
		) $charset_collate;";

		if ( $create_foreign_keys ) {
			$sqls[] = "ALTER TABLE `{$pref}g_booking`
			ADD CONSTRAINT `{$pref}fk_calendar_1sfg` FOREIGN KEY (`calendar_id`) REFERENCES `{$pref}g_calendars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
			ADD CONSTRAINT `{$pref}fk_customer_1sfg` FOREIGN KEY (`customer_id`) REFERENCES `{$pref}g_customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";


			$sqls[] = "ALTER TABLE `{$pref}g_calendar_inactivity_times`
			ADD CONSTRAINT `{$pref}fk_cal_time_1sfg` FOREIGN KEY (`calendar_id`) REFERENCES `{$pref}g_calendars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";


			$sqls[] = "ALTER TABLE `{$pref}g_calendar_timing`
			ADD CONSTRAINT `{$pref}fk_cal_1sfg` FOREIGN KEY (`calendar_id`) REFERENCES `{$pref}g_calendars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";


			$sqls[] = "ALTER TABLE `{$pref}g_extras_booking`
			ADD CONSTRAINT `{$pref}fk_booking_1sfg` FOREIGN KEY (`booking_id`) REFERENCES `{$pref}g_booking` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
			ADD CONSTRAINT `{$pref}fk_extra_id_1sfg` FOREIGN KEY (`extra_id`) REFERENCES `{$pref}g_extras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";


			$sqls[] = "ALTER TABLE `{$pref}g_extra_calendar`
			ADD CONSTRAINT `{$pref}fk_calendar_id_1sfg` FOREIGN KEY (`calendar_id`) REFERENCES `{$pref}g_calendars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
			ADD CONSTRAINT `{$pref}fk_ex_1sfg` FOREIGN KEY (`extra_id`) REFERENCES `{$pref}g_extras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
		}

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		$no_errors = true;
		foreach ( $sqls as $sql ) {
			if ( $wpdb->query( $sql ) === false ) {
				$no_errors = false;
				break;
			}
		}

		if ( $no_errors ) {
			$current_ver = 2;
			update_option( $db_option_name, $current_ver );
		}
	}
	if ($current_ver == 2) {
		foreach ( array(
			"{$pref}g_payment_systems_calendar",
			"{$pref}g_stripe_transactions",
			"{$pref}g_stripe_settings",
			"{$pref}g_payment_transactions",
			"{$pref}g_payment_systems") as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		$sqls = array();
		$sqls[] = "ALTER TABLE `{$pref}g_calendars` CHANGE `currency` `currency` VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD'";
		$sqls[] = "UPDATE {$pref}g_calendars SET currency = CASE currency WHEN '$' THEN 'USD' WHEN '€' THEN 'EUR' ELSE 'USD' END";

		$sqls[] = "CREATE TABLE `{$pref}g_payment_systems` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `name` varchar(255) NOT NULL,
			  PRIMARY KEY (`id`) 
			) $charset_collate";

		$sqls[] = "CREATE TABLE `{$pref}g_payment_systems_calendar` (
			`calendar_id` int(11) NOT NULL,
			`payment_system_id` int(11) NOT NULL,
			UNIQUE KEY `calendar_id` (`calendar_id`),
			KEY `{$pref}fk_g_ps` (`payment_system_id`)
		) $charset_collate";

		$sqls[] = "CREATE TABLE `{$pref}g_stripe_settings` ( 
			`id` INT NOT NULL AUTO_INCREMENT , 
  			`payment_system_id` int(11) NOT NULL,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , 
			`pkey` VARCHAR(255) NOT NULL , 
			`skey` VARCHAR(255) NOT NULL , 
			KEY `{$pref}key_ps_id_stripe_setts` (`payment_system_id`),
			PRIMARY KEY (`id`)) $charset_collate";

		$sqls[] = "CREATE TABLE `{$pref}g_payment_transactions` (
			 `id` int(11) NOT NULL AUTO_INCREMENT,
			 `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			 `booking_id` int(11) NOT NULL,
			 `payment_system_id` int(11) NOT NULL,
			 `status` enum('process','success','fail') NOT NULL DEFAULT 'process',
			 `modified_at` datetime DEFAULT NULL,
			 `fail_reason` varchar(255) NOT NULL,
			 `amount` decimal(10,2) NOT NULL, 
			 `currency` VARCHAR(5) NOT NULL DEFAULT 'USD',
			 PRIMARY KEY (`id`),
			 KEY `{$pref}fk_ps_id_key_ps` (`payment_system_id`),
			 KEY `{$pref}fk_ps_id_key_bk` (`booking_id`)
			) $charset_collate";

		$sqls[] = "CREATE TABLE `{$pref}g_stripe_transactions` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `transaction_id` int(11) NOT NULL,
			  `stripe_token` varchar(255) NOT NULL,
			  `stripe_ch_token` varchar(255) NOT NULL,
			  KEY `{$pref}key_stripe_tr_id` (`transaction_id`),
			  PRIMARY KEY (`id`)
			) $charset_collate";

		if ($create_foreign_keys) {
			$sqls[] = "ALTER TABLE `{$pref}g_payment_systems_calendar`
				ADD CONSTRAINT `{$pref}ps_fk_g_cal` FOREIGN KEY (`calendar_id`) REFERENCES `{$pref}g_calendars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				ADD CONSTRAINT `{$pref}ps_fk_g_ps` FOREIGN KEY (`payment_system_id`) REFERENCES `{$pref}g_payment_systems` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";

			$sqls[] = "ALTER TABLE `{$pref}g_payment_transactions` 
				ADD CONSTRAINT `{$pref}fk_ps_id` FOREIGN KEY (`payment_system_id`) REFERENCES `{$pref}g_payment_systems`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;";

			$sqls[] = "ALTER TABLE `{$pref}g_stripe_settings`
				ADD CONSTRAINT `{$pref}fk_ps_id_stripe_setts` FOREIGN KEY (`payment_system_id`) REFERENCES `{$pref}g_payment_systems` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";

			$sqls[] = "ALTER TABLE `{$pref}g_stripe_transactions`
				ADD CONSTRAINT `{$pref}fk_stripe_tr_id` FOREIGN KEY (`transaction_id`) REFERENCES `{$pref}g_payment_transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
		}

		$no_errors = true;
		foreach ( $sqls as $sql ) {
			if ( $wpdb->query( $sql ) === false ) {
				$no_errors = false;
				break;
			}
		}

		if ( $no_errors ) {
			$current_ver = 3;
			update_option( $db_option_name, $current_ver );
		}
	}
	
	/*foreach($tables as $table) {
		$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table?'exists':'NOT exists';
		echo $table . " - " . $exists . "\n ";
	}

	echo "Current ver: ".intval(get_option($db_option_name, 0));*/
}

