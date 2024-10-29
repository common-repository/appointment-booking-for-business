<?php
/**
 * Plugin Name: Appointment Booking For Business
 * Plugin URI: https://www.bookingforbusiness.app
 * Description: Appointment Booking For Business - Free Version - Easy and intuitive appointment booking calendar for businesses
 * Version: 1.0.2
 * Author: Doublesix
 * Author URI: http://www.doublesix.me
 * License: GPLv2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || die('Access Denied');
define("BKFORB_GCAL_PLUGIN_NAME",'appointment-booking-for-business');
define("BKFORB_GCAL_PLUGIN_SHORTCODE",'booking-for-business');

include_once ('spl_register.php');
include_once ('helpers.php');
include_once ('visits-handler.php');
include_once ('installdb.php');
include_once ('assets.php');
include_once ('shortcode.php');

//register_activation_hook( __FILE__, 'BKFORB_GCAL_g_cal_install' );
add_action('admin_init','BKFORB_GCAL_g_cal_install');
add_action('init', function(){
	if (!is_admin()){
		BKFORB_GCAL_glcalendar_enqueue_assets();
	}
});
add_shortcode(BKFORB_GCAL_PLUGIN_SHORTCODE, 'BKFORB_GCAL_handle_shortcode');

include_once ('ajax-controller.php');
include_once ('admininit.php');
