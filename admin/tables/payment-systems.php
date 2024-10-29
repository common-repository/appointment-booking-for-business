<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_PaymentSystemsListTable extends GCal_GeneralTable {
	protected $entity = \GLCalendar\PaymentSystem::class;
	protected $columns = array(
		'name' => 'Name',
		'description' => 'Gateway'
	);

	protected function column_description($item) {
		return lcfirst($item['description']);
	}
}