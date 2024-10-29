<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['view_id'])) {
	include_once ('forms/view_customer.php');
	$customer_id = intval($_GET['view_id']);
	/**
	 * @var \GLCalendar\Customer
	 */
	$customer = \GLCalendar\Customer::getById($customer_id);
	if ($customer!=null){
		init_customer_form($customer);
	} else {
		?>
        <div class="wrap"><h1>Customer #<?=$customer_id?> not found</h1></div>
		<?php
	}
} else {
include_once('tables/customers.php');
$table = new GCal_CustomersListTable();
?>
<div class="wrap">
    <h1><?=__('Customers')?></h1>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
		<?php
		$table->display();
		?>
    </form>
</div>
<?php } ?>