<?php

namespace GLCalendar\Payment\Gateway;

use GLCalendar\Entity;
use GLCalendar\DateTimeBook;
use GLCalendar\Calendar;
use GLCalendar\BookingController;
use GLCalendar\PaymentSystem;

defined('ABSPATH') || die('Access Denied');

abstract class BaseGateway extends Entity {
	const TRANSACTION = null;
	const DESCRIPTION = null;

	public $payment_system_id;

	public function attach_to_paymentsystem (PaymentSystem $ps){
		$this->payment_system_id = $ps->id;
	}
	public function create_description(DateTimeBook $booking) {
		return "Booking {$booking->date} - ".BookingController::formatMinutes($booking->time_from);
	}
	public function process_payment($post, DateTimeBook $booking, Calendar $calendar){}

	static public function get_for_paymentsystem (PaymentSystem $ps) {
		return static::getOne("payment_system_id = {$ps->id}");
	}

	static public function get_join_fields_string ($pseudo) {
		$fields = self::get_fields_pseudo($pseudo);
		$ignore = array('payment_system_id','id','created_at');
		foreach ($ignore as $v) unset($fields[array_search($pseudo.'.'.$v, $fields)]);
		return implode(", ", $fields);
	}

	static public function is_gateway($data) { return false; }
}