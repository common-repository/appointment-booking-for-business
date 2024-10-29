<?php

namespace GLCalendar\Payment\Gateway;

defined('ABSPATH') || die('Access Denied');

class StripeTransaction extends TransactionData implements IOnFlyTransaction {
	const TABLE_NAME = "g_stripe_transactions";
	const FIELDS = array('transaction_id','stripe_token', 'stripe_ch_token');

	public $stripe_token;
	public $stripe_ch_token;

	public function setToken($token) {
		$this->stripe_token = $token;
	}

	public function setProcessedTokens($data) {
		$this->stripe_token = $data['stripe_token'];
		$this->stripe_ch_token = $data['stripe_ch_token'];
	}
}