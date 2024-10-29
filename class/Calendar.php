<?php

namespace GLCalendar;

defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;

class Calendar extends Entity {
	const TABLE_NAME = "g_calendars";
	const FIELDS = array('name','price','currency','time_from','time_to','unavail_time_from',
						 'unavail_time_to','slot_duration');

	public $name;
	public $price;
	public $currency;
	public $time_from;
	public $time_to;
	public $unavail_time_from;
	public $unavail_time_to;
	public $slot_duration;

	static public $allowedCurrencies = array('USD','EUR');
	static public $currencyChar = array('USD'=>'$','EUR'=>'€');
	static public $defaultCurrency = 'USD';

	static public function validate_name ($val) {
		if (empty($val))
			throw new ValidationErrorException("Name is empty");
	}
	static public function sanitize_price($val) {return floatval($val);}
	static public function validate_currency ($val) {
		if (!in_array($val, self::$allowedCurrencies))
			throw new ValidationErrorException("Specified currency not allowed");
	}
	static public function sanitize_time_from($val){return intval($val);}
	static public function sanitize_time_to($val){return intval($val);}
	static public function validate_slot_duration($val) {
		if (!in_array($val, array(15,20,30,60)))
			throw new ValidationErrorException("slot duration invalid");
	}
	static public function full_validate(Calendar $obj) {
		if (!BKFORB_GCAL_is_time_pair_valid($obj->time_from, $obj->time_to))
			throw new ValidationErrorException("Time ranges are incorrect");
	}

	public function setInactivityTimes ($times) {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix.'g_calendar_inactivity_times',
			array(
				'calendar_id' => $this->id
			)
		);
		if ($r===false) return false;
		if (count($times) > 0) {
			$d = array();
			foreach($times as $v) {
				$d[] = "({$this->id}, ".abs(intval($v[0])).", ".abs(intval($v[1])).")";
			}
			$r = $wpdb->query(
				"INSERT INTO {$wpdb->prefix}g_calendar_inactivity_times(calendar_id,time_from,time_to) VALUES " .
				implode(",",$d)
			);
			return !!$r;
		}
		return true;
	}

	public function getInactivityTimes () {
		global $wpdb;
		$results = $wpdb->get_results("SELECT time_from, time_to FROM {$wpdb->prefix}g_calendar_inactivity_times WHERE calendar_id = {$this->id}");
		return array_map(function($d){return array($d->time_from,$d->time_to);}, $results);
	}

	/**
	 * @param array $extras
	 */
	public function setExtras (array $extras) {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix."g_extra_calendar",
			array(
				"calendar_id" => $this->id
			)
		);
		if ($r===false) return false;
		if (count($extras)>0) {
			$r = $wpdb->query(
				"INSERT INTO {$wpdb->prefix}g_extra_calendar(calendar_id,extra_id) VALUES({$this->id}, " .
				implode("),({$this->id}, ",array_map(function($e){return $e->id;},$extras))
				. ")"
			);
			return !!$r;
		}
		return true;
	}

	public function setPaymentSystem (PaymentSystem $payment_system = null) {
		$ps = array();
		if ($payment_system)$ps[]=$payment_system;
		return PaymentSystem::linkToCalendar($ps, $this);
	}

	/**
	 * @return PaymentSystem Payment System
	 */
	public function getPaymentSystem () {
		$pss = PaymentSystem::getForCalendar($this);
		if (empty($pss))return null;
		return $pss[0];
	}

	public function getExtras () {
		return Extra::getForCalendar($this);
	}

	public function setUnavailDays (array $timings) {
		global $wpdb;
		$r = $wpdb->delete(
			$wpdb->prefix."g_calendar_timing",
			array(
				'calendar_id' => $this->id
			)
		);
		if($r===false)return false;
		foreach ($timings as $k => $v) {
			if (empty(strtotime($v[0])) or empty(strtotime($v[1])))
				return false;
			$timings[$k][0] = date("Y-m-d",strtotime($v[0]));
			$timings[$k][1] = date("Y-m-d",strtotime($v[1]));
		}
		if (count($timings)==0)return true;
		$d = array();
		foreach ($timings as $v) {
			$d[] = "{$this->id},'unavail','{$v[0]}','{$v[1]}'";
		}
		return !!$wpdb->query("INSERT INTO {$wpdb->prefix}g_calendar_timing 
			(calendar_id, status, date_from, date_to) 
			VALUES (".implode("),(", $d).")");
	}

	public function getUnavailDays ($date_from, $date_to) {
		global $wpdb;
		$date_from = date("Y-m-d",strtotime($date_from));
		$date_to = date("Y-m-d",strtotime($date_to)-1);
		$results = $wpdb->get_results("
			SELECT date_from, date_to 
			FROM {$wpdb->prefix}g_calendar_timing 
			WHERE  ((date_from >= '$date_from' AND date_from <= '$date_to') OR 
					(date_to >= '$date_from' AND date_to <= '$date_to')) AND 
					status = 'unavail' AND 
					calendar_id = {$this->id}
		", ARRAY_A);
		return $results;
	}
}