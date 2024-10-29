<?php
defined('ABSPATH') || die('Access Denied');

function BKFORB_GCAL_gcal_num_2_places ($num) {
	return str_pad($num, 2, '0', STR_PAD_LEFT);
}

/**
 * Returns DateTimeZone based on WP settings
 * @return DateTimeZone
 */
function BKFORB_GCAL_gcal_wp_datetimezone() {
	$tz = get_option('timezone_string');
	if (!empty($tz)) return new DateTimeZone($tz);
	$offset = floatval(get_option('gmt_offset', 0));
	$minutes = $offset*60;
	$sign = $minutes>=0?'+':'';
	$hours = BKFORB_GCAL_gcal_num_2_places(floor( $minutes / 60));
	$mins = BKFORB_GCAL_gcal_num_2_places( $minutes % 60);
	return new DateTimeZone($sign.$hours.':'.$mins);
}