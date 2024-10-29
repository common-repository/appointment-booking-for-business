<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_CustomersListTable extends GCal_GeneralTable {
	protected $bulk_delete_available = false;
	protected $entity = \GLCalendar\Customer::class;
	protected $columns = array(
		'name' => 'Name',
		'email' => 'Email',
		'phone' => 'Phone',
		'created_at' => 'Registration date',
		'id' => 'ID'
	);
}