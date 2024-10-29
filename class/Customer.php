<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;

class Customer extends Entity {
	const TABLE_NAME = "g_customers";
	const FIELDS = array('name','email','phone');

	public $name;
	public $email;
	public $phone;

	static public function validate_name ($val) {
		if (empty($val))
			throw new ValidationErrorException("Name is empty");
	}
	static public function validate_email($value) {
		if (!filter_var($value, FILTER_VALIDATE_EMAIL))
			throw new ValidationErrorException("Email is invalid");
	}

	static public function findByEmail($email) {
		global $wpdb;
		return self::getOne($wpdb->prepare("email = %s",$email));
	}
}