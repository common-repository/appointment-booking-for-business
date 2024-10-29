<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['create'])) {
	include_once ('forms/edit_payment_system.php');
	gcalforb_init_payment_system_form(null);
} elseif (isset($_GET['view_id'])) {
	include_once ('forms/edit_payment_system.php');
	$ps_id = intval($_GET['view_id']);
	$payment_system = \GLCalendar\PaymentSystem::getById($ps_id);
	if ($payment_system!=null){
		gcalforb_init_payment_system_form($payment_system);
	} else {
		?>
        <div class="wrap"><h1>Payment Gateway #<?=$ps_id?> not found</h1></div>
		<?php
	}
} else {
include_once('tables/payment-systems.php');
$table = new GCal_PaymentSystemsListTable();
?>
<div class="wrap">
    <h1><?=__('Payment Gateways')?></h1>
    <a href="?page=<?=esc_attr($_REQUEST['page'])?>&create">
        <button class="button action">Add new payment gateway</button>
    </a>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
		<?php
		$table->display();
		?>
    </form>
</div>
<?php } ?>