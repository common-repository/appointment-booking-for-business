<?php


namespace GLCalendar\Payment\Gateway;

use GLCalendar\Entity;
use GLCalendar\Payment\Transaction;

defined('ABSPATH') || die('Access Denied');

abstract class TransactionData extends Entity implements ITransactionData {
	public $transaction_id;

	public function attach_to_transaction(Transaction $t) {
		$this->transaction_id = $t->id;
	}

	static public function get_join_fields_string ($pseudo) {
		$fields = self::get_fields_pseudo($pseudo);
		$ignore = array('transaction_id','id','created_at');
		foreach ($ignore as $v) unset($fields[array_search($pseudo.'.'.$v, $fields)]);
		return implode(", ", $fields);
	}

	static public function get_for_transaction (Transaction $t) {
		return static::getOne("transaction_id = {$t->id}");
	}
}