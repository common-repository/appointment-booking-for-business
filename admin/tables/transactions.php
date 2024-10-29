<?php
defined('ABSPATH') || die('Access Denied');
include_once ('general-table.php');

class GCal_TransactionsListTable extends GCal_GeneralTable {
	protected $entity = \GLCalendar\Payment\Transaction::class;
	protected $columns = array(
		'created_at' => 'Date Created',
		'id' => 'ID',
		'booking' => 'Booking',
		'payment_system_name' => 'Payment Gateway',
		'amount' => 'Amount',
		'status' => 'Status',
		'meta' => 'Meta'
	);
	protected $sortable_columns = array('created_at');

	protected function column_booking ($item) {
		if ($item['status'] == \GLCalendar\Payment\Transaction::STATUS_FAIL) {
			return "(not created)";
		}
		return "<a href='?page=gcal_parent&view_id=" . $item['booking_id'] ."'>{$item['booking_id']}</a>";
	}

	protected function column_status ($item) {
		if ($item['status'] == \GLCalendar\Payment\Transaction::STATUS_FAIL) {
			return "Failed. <br/>{$item['fail_reason']}";
		}
		if ($item['status'] == \GLCalendar\Payment\Transaction::STATUS_PROCESS) {
			return "Processing";
		}
		if ($item['status'] == \GLCalendar\Payment\Transaction::STATUS_SUCCESS) {
			return "Completed";
		}
		return "";
	}

	protected function column_created_at ($item) {
		return '<a href="?page='.urlencode(esc_attr($_REQUEST['page'])).'&view_id='.$item['id'].'">'.$item['created_at'].'</a>';
	}

	protected function column_amount ($item) {
		return $item['amount'] . ' ' . $item['currency'];
	}

	protected function column_meta ($item) {
		if (!empty($item['stripe_token'])) {
			$opt = "Token: ".$item['stripe_token'];
			if (!empty($item['stripe_ch_token']))
				$opt .= "<br/>Charge token: ".$item['stripe_ch_token'];
			return $opt;
		}
		return "";
	}
}