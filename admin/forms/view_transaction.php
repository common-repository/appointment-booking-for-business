<?php
defined('ABSPATH') || die('Access Denied');
function init_transaction_form (\GLCalendar\Payment\Transaction $transaction) {
    global $wpdb;

	/**
	 * @var $payment_system \GLCalendar\PaymentSystem
	 */
    $payment_system = \GLCalendar\PaymentSystem::getById($transaction->payment_system_id);

    $data = $transaction->load_data();


	?>
	<div class="wrap">
		<h1><?=__("View transaction")?> #<?=$transaction->id?></h1>
		<table class="form-table">
			<tr>
				<th>ID</th>
				<td><?=$transaction->id?></td>
			</tr>
			<tr>
				<th>Date</th>
				<td><?=$transaction->created_at?></td>
			</tr>
			<tr>
				<th>Booking</th>
				<td>
                    <?php if ($transaction->status!=\GLCalendar\Payment\Transaction::STATUS_FAIL) { ?>
					<a href="?page=gcal_parent&view_id=<?=$transaction->booking_id?>">
						<?=$transaction->booking_id?>
					</a>
                    <?php } else { ?>
                    Not created
                    <?php } ?>
				</td>
			</tr>
            <tr>
                <th>Amount</th>
                <td>
                    <?=$transaction->amount?> <?=$transaction->currency?>
                </td>
            </tr>
            <tr>
                <th>Payment Gateway</th>
                <td>
                    <a href="?page=<?= urlencode( BKFORB_GCAL_PLUGIN_NAME . '/admin/payment-systems.php') . '&view_id=' . $transaction->payment_system_id?>">
		                <?=$payment_system->name?>
                    </a>
                </td>
            </tr>
			<tr>
				<th>Status</th>
				<td>
                    <?php
                    switch ($transaction->status) {
                        case \GLCalendar\Payment\Transaction::STATUS_FAIL:
                            echo "Failed. Reason: ".$transaction->fail_reason;
                            break;
                        case \GLCalendar\Payment\Transaction::STATUS_SUCCESS:
                            echo "Completed";
                            break;
                        case \GLCalendar\Payment\Transaction::STATUS_PROCESS:
                            echo "In process...";
                            break;
                    }
                    ?>
				</td>
			</tr>
            <tr>
                <th>Meta</th>
                <td>
                    <?php
                    if (!empty($data->stripe_token)) {
	                    $opt = "Token: ".$data->stripe_token;
	                    if (!empty($data->stripe_ch_token))
		                    $opt .= "<br/>Charge token: ".$data->stripe_ch_token;
	                    echo $opt;
                    }
                    ?>
                </td>
            </tr>
		</table>
	</div>
	<?php
}
?>

