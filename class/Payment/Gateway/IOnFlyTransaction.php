<?php

namespace GLCalendar\Payment\Gateway;


interface IOnFlyTransaction extends ITransactionData {
	public function setToken($token);
	public function setProcessedTokens($data);
}