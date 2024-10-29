<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_CalendarsListTable extends GCal_GeneralTable {
	protected $bulk_delete_available = true;
	protected $entity = \GLCalendar\Calendar::class;
	protected $columns = array(
		'name' => 'Calendar Name',
		'shortcode' => 'Shortcode',
		'created_at' => 'Date Created',
	);
	protected $sortable_columns = ['created_at'];

	public function column_shortcode($item) {
		return "<input type='text' readonly size=30 onclick='this.focus();this.select();' value='[" . BKFORB_GCAL_PLUGIN_SHORTCODE . " id={$item['id']}]' />";
	}
}