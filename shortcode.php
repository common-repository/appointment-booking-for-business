<?php
defined('ABSPATH') || die('Access Denied');

include_once ('forms/calendar-form.php');

function BKFORB_GCAL_handle_shortcode ($atts){
	$atts = shortcode_atts(array('id'=>null),$atts,BKFORB_GCAL_PLUGIN_SHORTCODE);
	if (empty($atts['id']))return null;
	$id = intval($atts['id']);
	/**
	 * @var \GLCalendar\Calendar $calendar
	 */
	$calendar = \GLCalendar\Calendar::getById($id);
	if ($calendar==null)return null;

	if (null !== $payment_system = $calendar->getPaymentSystem()) {
		$handler = $payment_system->get_handler();
		if ($handler instanceof \GLCalendar\Payment\Gateway\IGatewayWithStyles) {
			$handler->enqueue_scripts();
		}
	}

	return gcal_create_calendar_form($calendar);
}