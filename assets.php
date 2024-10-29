<?php
defined('ABSPATH') || die('Access Denied');

function BKFORB_GCAL_glcalendar_enqueue_assets() {
	wp_enqueue_style( 'BKFORB_fontawesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
	wp_enqueue_style( 'BKFORB_glcalendar-style', plugin_dir_url( __FILE__ ) . '/assets/css/style.css', [],'1.2.0' );

	wp_register_script( 'BKFORB_glcalendar-script', plugin_dir_url( __FILE__ ) . '/assets/js/calendar.js', array("jquery"),'1.1.4');
	wp_localize_script( 'BKFORB_glcalendar-script', 'bkforb_gcal',
		array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'_nonce' => wp_create_nonce( 'bkforb_nonce' )
		)
	);
	wp_enqueue_script( "BKFORB_glcalendar-script");
}