<?php

namespace GLCalendar;


defined('ABSPATH') || die('Access Denied');

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Exception\SanitizationNotCalledException;
use GLCalendar\Exception\ValidationNotCalledException;

/**
 * Entity - is simple MySQL query builder using $wpdb
 *
 * Class Entity
 * @package GLCalendar
 */

abstract class Entity implements IEntity {
	public $id = null;
	public $created_at;

	const TABLE_NAME = null;
	const FIELDS = array('id','created_at');
	const NULLABLE = array();

	static public function get_table_static() {
		global $wpdb;
		return $wpdb->prefix.static::TABLE_NAME;
	}
	protected function table_name(){
		return self::get_table_static();
	}

	static private function is_field_nullable ($field) {
		return in_array($field, static::NULLABLE);
	}
	private function validation_after_all_method_exists () {
		return method_exists(get_called_class(), "full_validate");
	}
	private function validation_method_exists ($field) {
		return method_exists(get_called_class(), "validate_$field");
	}
	private function sanitization_method_exists ($field) {
		return method_exists(get_called_class(), "sanitize_$field");
	}
	private function perform_field_validation ($field, $value) {
		if ($this->validation_method_exists($field)) {
			try {
				call_user_func(array(get_called_class(), "validate_$field"), $value);
			} catch (ValidationErrorException $e) {
				return array(false, $e->getMessage());
			}
		}
		return array(true, null);
	}
	private function perform_after_all_validation () {
		if ($this->validation_after_all_method_exists()) {
			try {
				call_user_func(array(get_called_class(), "full_validate"), $this);
			} catch (ValidationErrorException $e) {
				return array(false, $e->getMessage());
			}
		}
		return array(true, null);
	}
	private function perform_field_sanitization ($field, $value) {
		if ($this->sanitization_method_exists($field)) {
			return call_user_func(array(get_called_class(), "sanitize_$field"), $value);
		}
		return sanitize_text_field($value);
	}

	static public function get_fields () {
		return array_merge(self::FIELDS, static::FIELDS);
	}

	static public function get_fields_pseudo($pseudo) {
		return array_map(function($field) use ($pseudo) { return $pseudo.'.'.$field; }, self::get_fields());
	}

	static public function get_fields_string ($pseudo = "") {
		return implode(", ", empty($pseudo)?self::get_fields():self::get_fields_pseudo($pseudo));
	}

	/**
	 * get_fields() without id and created_at
	 * @return array
	 */
	static private function get_native_fields () {
		$fields = self::get_fields();
		unset($fields[array_search('id',$fields)]);
		unset($fields[array_search('created_at',$fields)]);
		return $fields;
	}
	public function __construct($data=array()) {
		foreach($data as $k=>$v){
			if (in_array($k, self::get_fields())) {
				if ($k=='id')$v=intval($v);
				$this->$k = $v;
			}
		}
	}
	public static function last_db_error () {
		global $wpdb;
		return $wpdb->last_error;
	}
	public function last_error(){
		return self::last_db_error();
	}
	protected static function get_found_rows ($wpdb) {
		$found_rows = $wpdb->get_row("SELECT FOUND_ROWS() as cnt");
		return intval($found_rows->cnt);
	}
	public static function from_results ($results) {
		return array_map(function($r){return new static($r);}, $results);
	}
	public static function from_row ($row) {
		if ($row==null)return null;
		return new static($row);
	}

	static public function getOne($condition=null) {
		global $wpdb;
		if (!empty($condition))$condition = " WHERE $condition";
		return self::from_row($wpdb->get_row("
			SELECT ".implode(",",self::get_fields()) . "
			FROM ".self::get_table_static() . " 
			$condition
			LIMIT 1
		"));
	}

	static public function getById($id) {
		$id = intval($id);
		return self::getOne("id=$id");
	}

	static protected $lastRowsCount;
	static public function getLastRowsCount() {
		return self::$lastRowsCount;
	}
	static public function queryToList ($query) {
		global $wpdb;
		return self::from_results($wpdb->get_results($query));
	}
	static public function getList($condition = null, $ordering = null) {
		global $wpdb;
		if (!empty($condition))
			$condition = " WHERE ".$condition;

		if (!empty($ordering))
			$ordering = " ORDER BY ".$ordering;

		$results = self::queryToList("
			SELECT SQL_CALC_FOUND_ROWS ".implode(",",self::get_fields()) . " 
			FROM ".self::get_table_static() . " $condition $ordering
		");
		self::$lastRowsCount = self::get_found_rows($wpdb);
		return $results;
	}
	static public function getByIds(array $ids) {
		$ids = array_map('intval',$ids);
		$ids = array_filter($ids, function($d){return !empty($d);});
		if (empty($ids))return array();
		return static::getList("id IN (".implode(',',$ids).")");
	}
	static public function deleteByIds(array $ids) {
		global $wpdb;
		$ids = array_map('intval',$ids);
		$ids = array_filter($ids, function($d){return !empty($d);});
		if (empty($ids))return true;
		return $wpdb->query("
			DELETE FROM ".self::get_table_static()." 
			WHERE id in (".implode(",",$ids).")
		");
	}

	private $last_sanitized_fields = null;
	private $last_validated_fields = null;

	/**
	 * Fields without id and created_at
	 * @return array
	 */
	private function get_fields_with_values() {
		return array_combine(
			self::get_native_fields(),
			array_map(function($k){
				return $this->$k;
			},self::get_native_fields())
		);
	}
	private function is_fields_equal_with($arr) {
		return $arr === $this->get_fields_with_values();
	}
	private function is_sanitization_called() {
		return $this->is_fields_equal_with($this->last_sanitized_fields);
	}
	private function is_validation_called() {
		return $this->is_fields_equal_with($this->last_validated_fields);
	}
	public function sanitize() {
		$fields = $this->get_fields_with_values();
		foreach ($fields as $k => $v) {
			$this->$k = $fields[$k] = $this->perform_field_sanitization($k, $v);
		}
		$this->last_sanitized_fields = $fields;
	}
	private $_validation_errors = array();
	public function get_validation_errors() {
		return $this->_validation_errors;
	}
	public function get_validation_errors_string() {
		$errors = $this->get_validation_errors();
		return implode(", ", array_map(function($k, $v){return !empty($k)?"$k: $v":$v;}, array_keys($errors), array_values($errors)));
	}
	public function validate($throw_exception=false) {
		if (!$this->is_sanitization_called())
			throw new SanitizationNotCalledException(get_called_class());

		$this->_validation_errors = array();
		$fields = $this->get_fields_with_values();
		foreach ($fields as $k => $v) {
			list($result, $reason) = $this->perform_field_validation($k, $v);
			if (!$result) {
				$this->_validation_errors[$k] = $reason;
			}
		}
		list($result, $reason) = $this->perform_after_all_validation();
		if (!$result) {
			$this->_validation_errors[''] = $reason;
		}
		if (!empty($this->_validation_errors)) {
			if ($throw_exception)
				throw new \Exception($this->get_validation_errors_string());
			return false;
		}
		$this->last_validated_fields = $fields;
		return true;
	}

	private $_save_last_error = null;
	public function save_last_error() {
		return $this->_save_last_error;
	}
	public function now_localtime_string() {
		$now_localtime = new \DateTime(null, BKFORB_GCAL_gcal_wp_datetimezone());
		return $now_localtime->format('Y-m-d H:i:s');
	}
	public function delete() {
		global $wpdb;
		if (!empty($this->id)) {
			$delete_res = $wpdb->delete(self::get_table_static(),array('id'=>$this->id));
			if ($delete_res===false)return false;
			$this->id=null;
			return true;
		}
		return true;
	}
	public function save($skip_sanitize_validate=false) {
		global $wpdb;
		$now_localdatetime = $this->now_localtime_string();

		$d = $this->get_fields_with_values();
		if (!$skip_sanitize_validate) {
			if (!$this->is_sanitization_called()) {
				throw new SanitizationNotCalledException(get_called_class());
			}
			if (!$this->is_validation_called()) {
				throw new ValidationNotCalledException(get_called_class());
			}
		}
		foreach ($d as $k=>$v) {
			if ($v===null and !self::is_field_nullable($k))unset($d[$k]);
		}
		if (!empty($this->id)) {
			if (in_array('modified_at', self::get_fields())) {
				$d['modified_at'] = $this->now_localtime_string();
			}
			$upd_res = $wpdb->update(
				self::get_table_static(),
				$d,
				array('id'=>$this->id)
			);
			if ($upd_res === false) {
				$this->_save_last_error = $wpdb->last_error;
				return false;
			}
			return true;
		} else {
			$r = $wpdb->insert(
				self::get_table_static(),
				$d+array('created_at'=>$now_localdatetime)
			);
			if ($r===false) {
				$this->_save_last_error = $wpdb->last_error;
				return false;
			}
			$this->id = $wpdb->insert_id;
			return true;
		}
	}

	static public function startTransactionStatic() {
		global $wpdb;
		return $wpdb->query("START TRANSACTION");
	}
	static public function commitTransactionStatic() {
		global $wpdb;
		return $wpdb->query("COMMIT");
	}
	static public function rollbackTransactionStatic() {
		global $wpdb;
		return $wpdb->query("ROLLBACK");
	}
	public function startTransaction(){return self::startTransactionStatic();}
	public function commitTransaction(){return self::commitTransactionStatic();}
	public function rollbackTransaction(){return self::rollbackTransactionStatic();}

	public function lock($str, $timeout = 3) {
		global $wpdb;
		$timeout = intval($timeout);
		$d = $wpdb->get_row("SELECT GET_LOCK('{$str}',$timeout) as g_lock");
		return $d->g_lock;
	}
	public function releaseLock($str) {
		global $wpdb;
		$d = $wpdb->get_row("SELECT RELEASE_LOCK('{$str}') as g_lock");
		return $d->g_lock;
	}
}