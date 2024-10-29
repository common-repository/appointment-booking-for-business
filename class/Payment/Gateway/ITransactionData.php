<?php


namespace GLCalendar\Payment\Gateway;

use GLCalendar\IEntity;
use GLCalendar\Payment\Transaction;

interface ITransactionData extends IEntity {
	public function attach_to_transaction(Transaction $t);
	static public function get_join_fields_string ($pseudo);
	static public function get_for_transaction (Transaction $t);
}