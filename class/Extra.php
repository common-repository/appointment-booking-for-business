<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;

class Extra extends Entity {
	const TABLE_NAME = "g_extras";
	const FIELDS = array('name','price','sum_op');

	public $name;
	public $price;
	public $sum_op;

	static public function validate_name($val) {
		if (empty($val))
			throw new ValidationErrorException("Name is empty");
	}

	static public function sanitize_price($val) {
		return floatval($val);
	}

	static public function validate_sum_op ($val) {
		if (!in_array($val, array('%','+'))) {
			throw new ValidationErrorException("Name is empty");
		}
	}

	static public function getForCalendar(Calendar $calendar) {
		global $wpdb;
		return self::queryToList("
			SELECT e.id, e.name, e.price, e.sum_op 
			FROM ".self::get_table_static()." e 
			INNER JOIN {$wpdb->prefix}g_extra_calendar ec ON ec.extra_id = e.id 
			WHERE ec.calendar_id = {$calendar->id}
		");
	}

	static public function getForBooking(DateTimeBook $booking) {
		global $wpdb;
		return self::queryToList("
			SELECT e.id, e.name, e.price, e.sum_op 
			FROM ".self::get_table_static()." e 
			INNER JOIN {$wpdb->prefix}g_extras_booking eb ON eb.extra_id = e.id 
			WHERE eb.booking_id = {$booking->id}
		");
	}
}