<?php
defined('ABSPATH') || die('Access Denied');
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();
global $wpdb;
$pref = $wpdb->prefix;

$tables = array(
	"g_payment_systems_calendar",
	"g_stripe_transactions",
	"g_stripe_settings",
	"g_payment_transactions",
	"g_payment_systems",

	"g_calendar_timing",
	"g_calendar_inactivity_times",
	"g_extra_calendar",
	"g_extras_booking",
	"g_extras",
	"g_booking",
	"g_calendars",
	"g_customers"
);
$options = array(
	"gcal_db_version"
);
foreach ($options as $option)
	delete_option($option);

foreach ($tables as $table_name)
	$wpdb->query( "DROP TABLE IF EXISTS {$pref}{$table_name}" );
