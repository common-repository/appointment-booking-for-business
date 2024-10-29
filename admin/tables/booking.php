<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_BookingListTable extends GCal_GeneralTable {
	public function __construct($cond=null) {
		$this->prepare_condition = $cond;
		parent::__construct();
	}

	protected $bulk_delete_available = false;
	protected $entity = \GLCalendar\DateTimeBook::class;
	protected $columns = array(
		'customer_id' => 'Customer',
		'customer_email' => 'Customer Email',
		'date' => 'Date',
		'time' => 'Time',
		'price' => 'Amount',
		'created_at' => 'Created time',
		'calendar_id' => 'Calendar',
		'id' => 'ID',
		'payment' => 'Payment'
	);
	protected $sortable_columns = array(
		'id', 'date', 'created_at', 'calendar_id', 'customer_id'
	);
	protected function column_time ($item) {
		return \GLCalendar\BookingController::formatMinutes($item['time_from']).'-'.
		       \GLCalendar\BookingController::formatMinutes($item['time_to']);
	}
	protected function column_calendar_id ($item) {
		return $item['calendar_name'] ." (ID {$item['calendar_id']})";
	}
	protected function column_customer_id ($item) {
		return '<a href="?page='.urlencode(esc_attr($_REQUEST['page'])).'&view_id='.$item['id'].'">'.$item['customer_name'].'</a>';
	}
	protected function column_payment ($item) {
		if (!empty($item['transaction_id'])) {
			return "<a href='?page=".urlencode( BKFORB_GCAL_PLUGIN_NAME . '/admin/transactions.php') . '&view_id=' . $item['transaction_id'] ."'>{$item['payment_status']}</a>";
		} else {
			return "Not required";
		}
	}
}
