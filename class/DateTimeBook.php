<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;
use GLCalendar\Payment\Transaction;


class DateTimeBook extends Entity {
	const TABLE_NAME = "g_booking";
	const FIELDS = array('price','date','time_from','time_to','customer_id','calendar_id');

	public $price;
	public $date;
	public $time_from;
	public $time_to;
	public $customer_id;
	public $calendar_id;

	static public function sanitize_price($val) {
		return floatval($val);
	}
	static public function validate_date($val) {
		if (strtotime($val)===false)
			throw new ValidationErrorException("Date invalid");
	}
	static public function sanitize_time_from($val) {return intval($val);}
	static public function sanitize_time_to($val) {return intval($val);}
	static public function sanitize_customer_id($val) {return intval($val);}
	static public function sanitize_calendar_id($val) {return intval($val);}
	static public function full_validate (DateTimeBook $obj) {
		if ($obj->time_from >= $obj->time_to)
			throw new ValidationErrorException("Time ranges are invalid");
	}

	public function setExtras (array $extras) {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix."g_extras_booking",
			array(
				"booking_id" => $this->id
			)
		);
		if ($r===false) return false;
		if (count($extras)>0) {
			$r = $wpdb->query(
				"INSERT INTO {$wpdb->prefix}g_extras_booking(booking_id,extra_id) VALUES($this->id, " .
				implode("),({$this->id}, ",array_map(function($e){return $e->id;},$extras))
				. ")"
			);
			return !!$r;
		}
		return true;
	}

	public function getExtras () {
		return Extra::getForBooking($this);
	}

	static public function getList($condition = null, $ordering = null) {
		global $wpdb;

		if (!empty($condition))
			$condition = " WHERE ".$condition;

		if (!empty($ordering))
			$ordering = " ORDER BY ".$ordering;

		$results = $wpdb->get_results("
			SELECT SQL_CALC_FOUND_ROWS 
				b.id, b.`date`, b.time_from, b.time_to, 
				b.created_at, b.price,
				c.id as calendar_id, c.name as calendar_name,
				cust.id as customer_id, cust.name as customer_name, 
				cust.email as customer_email,
				p_tr.status as payment_status,
				p_tr.id as transaction_id
			FROM ".self::get_table_static() . " b 
			LEFT JOIN ".Calendar::get_table_static()." c ON c.id = b.calendar_id 
			LEFT JOIN ".Customer::get_table_static()." cust ON cust.id = b.customer_id 
			LEFT JOIN ".Transaction::get_table_static()." p_tr ON p_tr.booking_id = b.id
			$condition $ordering
		");
		self::$lastRowsCount = self::get_found_rows($wpdb);
		return $results;
	}
}