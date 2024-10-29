<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['view_id'])) {
	include_once ('forms/view_transaction.php');
	$id = intval($_GET['view_id']);

	$transaction = \GLCalendar\Payment\Transaction::getById($id);
	if ($transaction!=null){
		init_transaction_form($transaction);
	} else {
		?>
        <div class="wrap"><h1>Transaction #<?=$id?> not found</h1></div>
		<?php
	}
} else {
include_once('tables/transactions.php');
$table = new GCal_TransactionsListTable();
?>
<div class="wrap">
    <h1><?=__('Transactions log')?></h1>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
		<?php
		$table->display();
		?>
    </form>
</div>
<?php } ?>