<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;
use GLCalendar\Payment\Gateway\BaseGateway;
use GLCalendar\Payment\PSGateways;

class PaymentSystem extends Entity {
	const TABLE_NAME = "g_payment_systems";
	const FIELDS = array('name');

	public $name;

	static public function validate_name($val){
		if (empty($val))
			throw new ValidationErrorException("Name is empty");
	}

	/**
	 * @return BaseGateway gateway
	 */
	public function get_handler() {
		$gateways = PSGateways::getGateways();
		$gateway = $gateways[0];  // @TODO change logic when paypal will be added
		return $gateway::get_for_paymentsystem($this);
	}

	public function save( $skip_sanitize_validate = false ) {
		return parent::save( $skip_sanitize_validate );
	}

	/**
	 * @return PaymentSystem[]
	 */
	static public function get_all() {
		return parent::getList(null, "name");
	}

	static public function getForCalendar(Calendar $calendar) {
		global $wpdb;
		return parent::queryToList("
			SELECT ".self::get_fields_string("ps")." 
			FROM ".self::get_table_static()." ps
			INNER JOIN {$wpdb->prefix}g_payment_systems_calendar c2p ON c2p.payment_system_id = ps.id 
			WHERE c2p.calendar_id = {$calendar->id}
			");
	}

	static protected function unlinkFromCalendar(Calendar $calendar) {
		global $wpdb;
		$r = $wpdb->query("
			DELETE FROM {$wpdb->prefix}g_payment_systems_calendar WHERE calendar_id = {$calendar->id}
			");
		return $r!==false;
	}

	/**
	 * @param PaymentSystem[] $payment_systems
	 * @param Calendar $calendar
	 *
	 * @return bool
	 */
	static public function linkToCalendar(array $payment_systems, Calendar $calendar) {
		global $wpdb;
		if (!self::unlinkFromCalendar($calendar)) return false;
		if (empty($payment_systems))return true;
		$payment_systems_ids = array_map(function($ps){return $ps->id;}, $payment_systems);
		$values_arr = array();
		foreach ($payment_systems_ids as $ps_id) {
			$values_arr[] = "({$calendar->id}, $ps_id)";
		}
		$values = implode(", ",$values_arr);
		$r = $wpdb->query("
			INSERT INTO {$wpdb->prefix}g_payment_systems_calendar (calendar_id, payment_system_id) VALUES $values
		");
		return $r!==false;
	}

	static public function getList($condition = null, $ordering = null) {
		global $wpdb;

		if (!empty($condition))
			$condition = " WHERE ".$condition;

		if (!empty($ordering))
			$ordering = " ORDER BY ".$ordering;

		$gateways = PSGateways::getGateways();  // @TODO change when paypal added
		$gateway = $gateways[0];

		$g_table = $gateway::get_table_static();
		$g_fields = $gateway::get_join_fields_string('gt');

		$results = $wpdb->get_results("
			SELECT SQL_CALC_FOUND_ROWS 
				ps.id, ps.created_at, ps.name, 
				$g_fields
			FROM ".self::get_table_static() . " ps 
			LEFT JOIN $g_table gt ON gt.id = ps.id
			$condition $ordering
		");
		self::$lastRowsCount = self::get_found_rows($wpdb);
		foreach ($results as $k => $r) {
			if ($gateway::is_gateway($r)) {
				$results[$k]->description = $gateway::DESCRIPTION;  // @TODO change when paypal added
			}
		}
		return $results;
	}

	public function __construct($data = array() ) {
		parent::__construct( $data );
	}
}