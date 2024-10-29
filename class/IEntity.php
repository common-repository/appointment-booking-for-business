<?php

namespace GLCalendar;


interface IEntity {
	static public function get_table_static();
	static public function get_fields ();
	static public function get_fields_pseudo($pseudo);
	static public function get_fields_string ($pseudo = "");
	public static function last_db_error ();
	public function last_error();
	public static function from_results ($results);
	public static function from_row ($row);
	static public function getOne($condition=null);
	static public function getById($id);
	static public function getLastRowsCount();
	static public function queryToList ($query);
	static public function getList($condition = null, $ordering = null);
	static public function getByIds(array $ids);
	static public function deleteByIds(array $ids);
	public function sanitize();
	public function get_validation_errors();
	public function get_validation_errors_string();
	public function validate($throw_exception=false);
	public function save_last_error();
	public function now_localtime_string();
	public function delete();
	public function save($skip_sanitize_validate=false);
	static public function startTransactionStatic();
	static public function commitTransactionStatic();
	static public function rollbackTransactionStatic();
	public function startTransaction();
	public function commitTransaction();
	public function rollbackTransaction();

	public function lock($str, $timeout);
	public function releaseLock($str);
}