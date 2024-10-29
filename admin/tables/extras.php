<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_ExtrasListTable extends GCal_GeneralTable {
	protected $bulk_delete_available = true;
	protected $entity = \GLCalendar\Extra::class;
	protected $columns = array(
		'name' => 'Name',
		'price' => 'Price'
	);
	protected function column_price ($item) {
		return "{$item['price']}{$item['sum_op']}";
	}
}