<?php
defined('ABSPATH') || die('Access Denied');

add_action('admin_menu', function(){
	add_menu_page(
		'Booking For Business',
		'Booking For Business',
		'administrator',
		'gcal_parent',
		function(){include_once (plugin_dir_path(__FILE__).'admin/applications.php');},
		plugins_url('/assets/img/menu_icon.png', __FILE__),
		null
	);
	add_submenu_page(
		'gcal_parent',
		'Scheduled Appointments',
		'Scheduled Appointments',
		'administrator',
		'gcal_parent',
		function(){include_once (plugin_dir_path(__FILE__).'admin/applications.php');}
	);
	add_submenu_page(
		'gcal_parent',
		'Calendars',
		'Calendars',
		'administrator',
		plugin_dir_path(__FILE__).'admin/calendars.php'
	);
	add_submenu_page(
		'gcal_parent',
		'Extras',
		'Extras',
		'administrator',
		plugin_dir_path(__FILE__).'admin/extras.php'
	);
	add_submenu_page(
		'gcal_parent',
		'Customers',
		'Customers',
		'administrator',
		plugin_dir_path(__FILE__).'admin/customers.php'
	);
	add_submenu_page(
		'gcal_parent',
		'Payment Gateways',
		'Payment Gateways',
		'administrator',
		plugin_dir_path(__FILE__).'admin/payment-systems.php'
	);
	add_submenu_page(
		'gcal_parent',
		'Payment Transactions',
		'Payment Transactions',
		'administrator',
		plugin_dir_path(__FILE__).'admin/transactions.php'
	);
});
add_action('admin_enqueue_scripts',function($page){
	if ( stripos($page, BKFORB_GCAL_PLUGIN_NAME . '/') != 0)return;

	wp_register_script( 'BKFORB_gcal-scripts', plugin_dir_url( __FILE__ ) . '/assets/js/admin.js', array("jquery"),'1.0.1');
	wp_localize_script( 'BKFORB_gcal-scripts', 'bkforb_gcal_admin',
		array(
			'_nonce' => wp_create_nonce( 'bkforb_nonce_admin' ),
			'ajaxurl' => admin_url('admin-ajax.php'),
		)
	);
	wp_enqueue_script('BKFORB_gcal-scripts');
	wp_enqueue_style('BKFORB_gcal-faicons',
		'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'		);
	wp_enqueue_style('BKFORB_gcal-styles',
		plugins_url('/assets/css/admin.css',__FILE__),
		array(),
		'1.0.1');
});