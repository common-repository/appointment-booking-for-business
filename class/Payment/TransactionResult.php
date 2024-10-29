<?php

namespace GLCalendar\Payment;


class TransactionResult {
	public $is_success;
	public $fail_reason;
	public $data;
	public function __construct($is_success, $fail_reason, $data=array()) {
		$this->is_success = $is_success;
		$this->fail_reason = $fail_reason;
		$this->data = $data;
	}
}