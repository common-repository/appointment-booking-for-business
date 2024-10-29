<?php

namespace GLCalendar\Payment;

use GLCalendar\Payment\Gateway\Stripe;

defined('ABSPATH') || die('Access Denied');

class PSGateways {
	static protected $gateways = array(
		Stripe::class
	);
	static public function getGateways() {
		return self::$gateways;
	}
}