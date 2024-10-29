<?php
defined('ABSPATH') || die('Access Denied');

function gcalforb_init_payment_system_form (\GLCalendar\PaymentSystem $payment_system = null) {
    $gateway = null;
	if (!empty($_POST)) {
		if (!check_admin_referer('bkforb_nonce_admin', '_nonce')) {
			echo "<div class='notice notice-error is-disimissible'>
                <p>Unable to create/edit the gateway. (Nonce error)</p>
                </div>";
		} else {
			if ($payment_system == null)
				$payment_system = new \GLCalendar\PaymentSystem();

			try {
			    $edit_secret = !empty($_POST['edit_secret']);

			    if ($edit_secret && empty($_POST['stripe_secret_key']))
			        throw new Exception("Secret key is empty!");

			    $payment_system->name = $_POST['payment_system_name'];

				$gateway = $payment_system->get_handler();

				if ($gateway == null)
					$gateway = new \GLCalendar\Payment\Gateway\Stripe();   // @TODO change when paypal added

				$gateway->pkey = $_POST['stripe_publishable_key'];  // @TODO change when paypal added
				if ($edit_secret)
				    $gateway->skey = $_POST['stripe_secret_key'];  // @TODO change when paypal added

				$payment_system->startTransaction();
				$payment_system->sanitize();
				$payment_system->validate(true);

				if ($payment_system->save()) {
					$gateway->attach_to_paymentsystem($payment_system);
					$gateway->sanitize();
					$gateway->validate(true);
					if (!$gateway->save())
						throw new Exception($gateway->last_error());

					$payment_system->commitTransaction();
					$new_loc = '?page=' . esc_js($_REQUEST['page']) . '&view_id=' . $payment_system->id;
					?><script>window.location.href = '<?=$new_loc?>';</script><?php
				} else {
					throw new Exception($payment_system->last_error());
				}
			} catch (Exception $e) {
				$payment_system->rollbackTransaction();
				echo "<div class='notice notice-error is-disimissible'>
                    <p>Unable to create/edit the payment gateway. Errors: {$e->getMessage()}</p>
                    </div>";
			}
		}
	} elseif ($payment_system != null) {
	    $gateway = $payment_system->get_handler();
    }
?>
<div class="wrap">
	<h1><?=$payment_system==null?__('Add Payment Gateway'):__('Edit Payment Gateway')?></h1>
	<form method="post" action="">
		<table class="form-table">
			<?php if ($payment_system != null) { ?>
			<tr>
				<th>ID: <?=$payment_system->id?></th>
				<td></td>
			</tr>
			<?php } ?>
			<tr>
				<th><?=__('Name')?></th>
				<td>
					<input type="text"
					       name="payment_system_name"
					       class="regular-text"
					       required
					       value="<?=$payment_system==null?'':esc_html($payment_system->name)?>"
					/>
				</td>
			</tr>
			<tr>
				<th><?=__('Stripe Publishable Key')?></th>
				<td>
					<input type="text"
					       name="stripe_publishable_key"
					       class="regular-text"
					       required
					       value="<?=$payment_system==null?'':esc_html($gateway->pkey)?>"
					/>
				</td>
			</tr>
			<tr>
				<th><?=__('Stripe Secret Key')?></th>
				<td>
                    <?php if ($payment_system==null) { ?>
                        <input type="text"
                               name="stripe_secret_key"
                               class="regular-text"
                               required
                               value=""
                        />
                        <input name="edit_secret" type="hidden" id="edit_secret" value="1">
                    <?php } else { ?>
                        <input type="password"
                               name="stripe_secret_key_pwd"
                               class="regular-text"
                               value="111111111111111111111111"
                        />
                        <input type="text"
                               name="stripe_secret_key"
                               class="regular-text"
                               value=""
                               style="display: none"
                        />
                        <label for="edit_secret">
                            <input name="edit_secret" type="checkbox" id="edit_secret" value="1" onchange="handleEditSecret(this)">
                            Edit
                        </label>
                    <?php } ?>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="hidden" name="_nonce" value="<?=wp_create_nonce( 'bkforb_nonce_admin' )?>" />
			<input type="submit" name="submit"
			       class="button button-primary"
			       value="<?=$payment_system==null?__('Create'):__('Save')?>" />
		</p>
	</form>
    <script>
        function handleEditSecret (input) {
            if (input.checked) {
                document.querySelector('[name=stripe_secret_key_pwd]').style.display = 'none';
                document.querySelector('[name=stripe_secret_key]').style.display = 'inline';
            } else {
                document.querySelector('[name=stripe_secret_key_pwd]').style.display = 'inline';
                document.querySelector('[name=stripe_secret_key]').style.display = 'none';
            }
        }
    </script>
</div>

<?php } ?>