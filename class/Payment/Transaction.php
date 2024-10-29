<?php

namespace GLCalendar\Payment;

use GLCalendar\Entity;
use GLCalendar\DateTimeBook;
use GLCalendar\PaymentSystem;

use GLCalendar\Exception\ValidationErrorException;

defined('ABSPATH') || die('Access Denied');

class Transaction extends Entity {
	const TABLE_NAME = "g_payment_transactions";
	const FIELDS = array('booking_id','payment_system_id','status',
						'amount', 'currency',
						'modified_at', 'fail_reason');

	const STATUS_PROCESS = 'process';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAIL = 'fail';

	public $booking_id;
	public $amount;
	public $currency;
	public $payment_system_id;
	public $status;
	public $modified_at;
	public $fail_reason;

	static public function validate_payment_system_id($val){return !empty($val);}
	static public function validate_booking_id($val){return !empty($val);}

	static public function validate_status($val) {
		if (!in_array($val, array(self::STATUS_FAIL,self::STATUS_PROCESS,self::STATUS_SUCCESS)))
			throw new ValidationErrorException("Wrong status value");
	}

	public function get_text_status () {
		switch ($this->status) {
			case self::STATUS_PROCESS:
				return "In process";
			case self::STATUS_FAIL:
				return "In process";
			case self::STATUS_SUCCESS:
				return "Completed";
		}
		return "";
	}

	public function is_failed () {
		return $this->status == self::STATUS_FAIL;
	}

	public function load_data () {
		$gateways = PSGateways::getGateways();
		$gateway = $gateways[0];
		$transaction_class = $gateway::TRANSACTION;
		return $transaction_class::get_for_transaction($this);
	}

	static public function getForBooking (DateTimeBook $booking) {
		return self::getOne("booking_id = {$booking->id}");
	}

	static public function getList($condition = null, $ordering = null) {
		global $wpdb;

		if (!empty($condition))
			$condition = " WHERE ".$condition;

		if (!empty($ordering))
			$ordering = " ORDER BY ".$ordering;

		$gateways = PSGateways::getGateways();
		$gateway = $gateways[0];
		$transaction_class = $gateway::TRANSACTION;
		$transaction_fields = $transaction_class::get_join_fields_string('gt_t');
		$transaction_table = $transaction_class::get_table_static();

		$results = $wpdb->get_results("
			SELECT SQL_CALC_FOUND_ROWS 
				".self::get_fields_string("tr").",
				ps.name as payment_system_name,
				bk.calendar_id as booking_calendar_id,
				$transaction_fields
			FROM ".self::get_table_static() . " tr
			LEFT JOIN ".PaymentSystem::get_table_static()." ps ON ps.id = tr.payment_system_id 
			LEFT JOIN ".DateTimeBook::get_table_static()." bk ON bk.id = tr.booking_id 
			LEFT JOIN $transaction_table gt_t ON gt_t.transaction_id = tr.id
			$condition $ordering
		");
		self::$lastRowsCount = self::get_found_rows($wpdb);
		return $results;
	}
}