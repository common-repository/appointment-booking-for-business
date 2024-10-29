<?php

namespace GLCalendar\Payment\Gateway;

use GLCalendar\Exception\ValidationErrorException;
use GLCalendar\Payment\PSGateways;
use GLCalendar\Payment\Transaction;
use GLCalendar\Payment\TransactionResult;
use GLCalendar\DateTimeBook;
use GLCalendar\Calendar;

defined('ABSPATH') || die('Access Denied');

require_once (__DIR__.'/../../../vendor/autoload.php');

class Stripe extends BaseGateway implements IOnFlyGateway, IGatewayWithStyles {
	const TABLE_NAME = "g_stripe_settings";
	const FIELDS = array('payment_system_id', 'pkey', 'skey');

	const TRANSACTION = StripeTransaction::class;
	const DESCRIPTION = 'stripe';

	public $pkey;
	public $skey;

	static public function is_gateway( $data ) {
		return !empty($data->pkey);
	}

	static public function validate_pkey($val){
		if (empty($val))
			throw new ValidationErrorException("Public key is empty");
	}
	static public function validate_skey($val) {
		if (empty($val))
			throw new ValidationErrorException("Secret key is empty");
	}

	public function is_charge_process( $post ) {
		return $this->fetch_token_post($post) !== null;
	}

	public function fetch_token_post ($post) {
		if (array_key_exists('stripeToken', $post))
			return $post['stripeToken'];
		return null;
	}

	/**
	 * @param $post
	 * @param $amount
	 * @param $currency
	 * @param $description
	 *
	 * @return TransactionResult
	 */
	public function process_payment ($post, DateTimeBook $booking, Calendar $calendar){
		if (!$this->is_charge_process($post))
			return new TransactionResult(false, 'Token not provided');
		$token = $this->fetch_token_post($post);
		\Stripe\Stripe::setApiKey($this->skey);
		try {
			$charge = \Stripe\Charge::create([
				'amount' => round($booking->price*100),
				'currency' => strtolower($calendar->currency),
				'description' => $this->create_description($booking),
				'source' => $token,
				'metadata' => ['booking_id' => $booking->id],
			]);
			if ($charge->paid) {
				return new TransactionResult(true, null, array(
					'stripe_token' => $token,
					'stripe_ch_token' => $charge->id
				));
			} else {
				throw new \Exception("");
			}
		}
		catch (\Stripe\Error\Card $e) {
			$json = $e->getJsonBody();
			return new TransactionResult(false, $json['error']);
		}
		catch (\Stripe\Error\InvalidRequest $e) {return new TransactionResult(false, "Stripe invalid request");}
		catch (\Stripe\Error\ApiConnection $e) {return new TransactionResult(false, "Network problem");}
		catch (\Stripe\Error\Api $e) {return new TransactionResult(false, "Stripe API error");}
		catch (\Stripe\Error\Authentication $e) {return new TransactionResult(false, "Stripe auth fail");}
		catch (\Exception $e) {
			return new TransactionResult(false, "Something gone wrong");
		}
	}

	public function enqueue_scripts () {
		wp_enqueue_script("BKFORB_glcalendar-stripe-checkout", "https://checkout.stripe.com/checkout.js");
		wp_register_script( 'BKFORB_glcalendar-script-stripe-conf',
			plugin_dir_url(
				 dirname(dirname(__DIR__))
			) . 'assets/js/stripe-conf.js',
			array("jquery","BKFORB_glcalendar-stripe-checkout","BKFORB_glcalendar-script"),
			'1.0.1'
		);
		wp_localize_script( 'BKFORB_glcalendar-script-stripe-conf', 'bkforb_payment_system',
			array(
				'name' => 'stripe',
				'pkey' => $this->pkey,
				'onpayclick' => 'stripeOnPayButtonClick'
			)
		);
		wp_enqueue_script( "BKFORB_glcalendar-script-stripe-conf");
	}
}

