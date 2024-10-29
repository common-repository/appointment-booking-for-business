<?php
defined('ABSPATH') || die('Access Denied');
function init_booking_form (\GLCalendar\DateTimeBook $booking) {
    global $wpdb;
	/**
	 * @var \GLCalendar\Customer
	 */
	$customer = \GLCalendar\Customer::getById($booking->customer_id);
	$calendar = \GLCalendar\Calendar::getById($booking->calendar_id);
	$extras = $booking->getExtras();

	/**
	 * @var $transaction \GLCalendar\Payment\Transaction
	 */
    $transaction = \GLCalendar\Payment\Transaction::getForBooking($booking);
	if (!empty($_POST)) {
		if (!empty($_POST['delete'])) {
		    if (!check_admin_referer('bkforb_nonce_admin', '_nonce')) {
			    echo "<div class='notice notice-error is-disimissible'>
                    <p>Unable to delete the booking. (Nonce error)</p>
                    </div>";
            } else {
		        if (!empty($transaction) and !$transaction->is_failed()) {
			        echo "<div class='notice notice-error is-disimissible'>
                        <p>Unable to delete the booking since there is a paid transaction</p>
                        </div>";
                } else {
			        if ( \GLCalendar\DateTimeBook::deleteByIds( array( $booking->id ) ) ) {
				        \GLCalendar\EmailNofitication::booking_deleted( $booking, $customer );
				        $new_loc = '?page=' . esc_js( $_REQUEST['page'] );
				        ?>
                        <script>window.location.href = '<?=$new_loc?>';</script><?php
			        } else {
				        echo "<div class='notice notice-error is-disimissible'>
                        <p>Unable to delete the booking {$wpdb->last_error}</p>
                        </div>";
			        }
		        }
		    }
		}
	}

	?>
	<div class="wrap">
		<h1><?=__("View booking")?> #<?=$booking->id?></h1>
		<table class="form-table">
			<tr>
				<th>ID</th>
				<td><?=$booking->id?></td>
			</tr>
			<tr>
				<th>Date</th>
				<td><?=$booking->date?></td>
			</tr>
			<tr>
				<th>Time</th>
				<td>
					<?=\GLCalendar\BookingController::formatMinutes($booking->time_from)?> -
					<?=\GLCalendar\BookingController::formatMinutes($booking->time_to)?>
				</td>
			</tr>
			<tr>
				<th>Booking created</th>
				<td><?=$booking->created_at?></td>
			</tr>
			<tr>
				<th>Calendar</th>
				<td>
					<a href="?page=<?= urlencode( BKFORB_GCAL_PLUGIN_NAME . '/admin/calendars.php') . '&view_id=' . $calendar->id?>">
						<?=$calendar->name?> (ID: <?=$calendar->id?>)
					</a>
				</td>
			</tr>
			<tr>
				<th>Customer</th>
				<td>
					<a href="?page=<?= urlencode( BKFORB_GCAL_PLUGIN_NAME . '/admin/customers.php') . '&view_id=' . $customer->id?>">
						<?=$customer->name?> (ID: <?=$customer->id?>)
					</a>
				</td>
			</tr>
            <tr>
                <th>Customer Email</th>
                <td>
	                <?=esc_html($customer->email)?>
                </td>
            </tr>
			<tr>
				<th>Extras booked</th>
				<td>
					<?php
					if (empty($extras)) {
						echo "Empty";
					} else {
						/**
						 * @var \GLCalendar\Extra $e
						 */
						foreach ($extras as $e) {
							echo esc_html($e->name) . '<br/>';
						}
					}
					?>
				</td>
			</tr>
            <tr>
                <th>Total amount</th>
                <td>$<?=$booking->price?></td>
            </tr>
            <?php
            if (!empty($transaction)) {
                ?>
                <tr>
                    <th>Transaction</th>
                    <td>
                        <a href="?page=<?= urlencode( BKFORB_GCAL_PLUGIN_NAME . '/admin/transactions.php') . '&view_id=' . $transaction->id?>">
	                        <?=$transaction->get_text_status()?> (ID: <?=$transaction->id?>)
                        </a>
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <th></th>
                <td>
                    <?php if (empty($transaction) or $transaction->is_failed()) { ?>
                    <form method="post" action="">
                        <p class="submit">
                            <input type="hidden" name="_nonce" value="<?=wp_create_nonce( 'bkforb_nonce_admin' )?>" />
                            <input type="submit" name="delete"
                                   class="button button-link-delete"
                                   value="<?=__("Delete")?>" />
                        </p>
                    </form>
                    <?php } ?>
                </td>
            </tr>
		</table>
	</div>
	<?php
}
?>

