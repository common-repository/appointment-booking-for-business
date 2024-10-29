<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['view_id'])) {
	include_once ('forms/view_booking.php');
	$booking_id = intval($_GET['view_id']);
	/**
	 * @var \GLCalendar\Customer
	 */
	$booking = \GLCalendar\DateTimeBook::getById($booking_id);
	if ($booking!=null){
		init_booking_form($booking);
	} else {
		?>
        <div class="wrap"><h1>Booking #<?=$booking_id?> not found</h1></div>
		<?php
	}
} else {
include_once('tables/booking.php');
$table = new GCal_BookingListTable();
?>
<div class="wrap">
    <h1><?=__('Scheduled Appointments')?></h1>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
		<?php
		$table->display();
		?>
    </form>
</div>
<?php } ?>