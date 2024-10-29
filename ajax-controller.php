<?php
defined('ABSPATH') || die('Access Denied');

include_once ('forms/booking-window.php');

function BKFORB_GCAL_is_time_pair_valid ($from, $to, $empty_avail = false) {
	return !(($empty_avail?$from>$to:$from>=$to)
	or $from < 0 or $from > 24*60
	or $to < 0 or $to > 24*60);
}

function BKFORB_GCAL_gcal_edit_calendar () {
	if(!check_ajax_referer('bkforb_nonce_admin', '_nonce')) {
		header("HTTP/1.1 400 Bad Request");
		wp_die();
		die;
	}

	$current_user = wp_get_current_user();
	$new_calendar = null;
	$transaction_rollbacked = false;
	try {
		if (empty($current_user))
			throw new Exception('Not authorized');
		if (empty($_POST))
			throw new Exception('Empty');

		$calendar_id = intval($_POST['calendar_id']);

		$extras_to_link = array();
		if (is_array($_POST['extras'])) {
			$extras_to_link = \GLCalendar\Extra::getByIds(
				array_map('intval', $_POST['extras'])
			);
		}

		$new_calendar = empty($calendar_id)?new \GLCalendar\Calendar():\GLCalendar\Calendar::getById($calendar_id);
		if (empty($new_calendar))
			throw new Exception("Calendar not found");

		$inactive_times = array();
		if (is_array($_POST['inactive_time_from']) and is_array($_POST['inactive_time_from']) and
			count($_POST['inactive_time_from']) == count($_POST['inactive_time_from'])) {
			for ( $i = 0; $i < count( $_POST['inactive_time_from'] ); $i ++ ) {
				$from = intval( $_POST['inactive_time_from'][ $i ] );
				$to   = intval( $_POST['inactive_time_to'][ $i ] );
				if ( ! BKFORB_GCAL_is_time_pair_valid( $from, $to, true ) ) {
					throw new Exception( "Inactivity times invalid" );
				}
				$inactive_times[] = array( $from, $to );
			}
		}

		$payment_system_id = intval($_POST['payment_system']);
		$payment_system = null;
		if (!empty($payment_system_id)) {
			if (null == $payment_system = \GLCalendar\PaymentSystem::getById($payment_system_id))
				throw new Exception("Specified payment gateway does not exists. Please reload the page");
		} else {
			$payment_system_id = null;
		}

		$new_calendar->name          = sanitize_text_field( $_POST['calendar_name'] );
		$new_calendar->time_from     = intval( $_POST['time_from'] );
		$new_calendar->time_to       = intval( $_POST['time_to'] );
		$new_calendar->slot_duration = intval( $_POST['slot_duration'] );
		$new_calendar->price         = array_key_exists( 'price', $_POST ) ? abs( floatval( $_POST['price'] ) ) : 0;
		$new_calendar->currency      = sanitize_text_field( $_POST['currency'] );

		if (empty($new_calendar->name))
			throw new Exception("Name is empty");

		if (!BKFORB_GCAL_is_time_pair_valid($new_calendar->time_from, $new_calendar->time_to))
			throw new Exception("Times ranges are invalid");

		if (!in_array($new_calendar->slot_duration, array(15,20,30,60)))
			throw new Exception("Slot duration is invalid");

		if (!in_array($new_calendar->currency, \GLCalendar\Calendar::$allowedCurrencies))
			throw new Exception("Currency is invalid");

		$new_calendar->sanitize();
		$new_calendar->validate(true);

		$selections = array_key_exists('selections', $_POST)?json_decode(stripslashes($_POST['selections'])):array();
		if (!is_array($selections))$selections = array();  // json_decode will return NULL if invalid json array given
		foreach ($selections as $k => $v) {
			if (!is_array($v) or strtotime($v[0]) === false or strtotime($v[1]) === false)
				throw new Exception("Selections are invalid");
		}

		$new_calendar->startTransaction();
		if ( $p1=$new_calendar->save() and $p2=$new_calendar->setExtras( $extras_to_link )
		                               and $p3=$new_calendar->setUnavailDays( $selections )
		                               and $p4=$new_calendar->setInactivityTimes($inactive_times)
									   and $p5=$new_calendar->setPaymentSystem($payment_system)
		) {
			$new_calendar->commitTransaction();
			echo json_encode( array(
				'success'     => 'success',
				'calendar_id' => $new_calendar->id
			) );
		} else {
			$new_calendar->rollbackTransaction();
			$transaction_rollbacked = true;
			//ob_start();var_dump($p1,$p2,$p3,$p4,$p5);$dbg=ob_get_clean();
			throw new Exception("Unable to save calendar");
		}
	} catch (Exception $e) {
		if ($new_calendar and !$transaction_rollbacked)
			$new_calendar->rollbackTransaction();
		echo json_encode(array('success'=>'error','reason'=>$e->getMessage()));
	}
	wp_die();die;
}

function BKFORB_GCAL_gcal_book () {
	if(!check_ajax_referer('bkforb_nonce', '_nonce')) {
		header("HTTP/1.1 400 Bad Request");
		wp_die();
		die;
	}
	$now_localtime = new DateTime(null, BKFORB_GCAL_gcal_wp_datetimezone());
	$today_time = strtotime($now_localtime->format('Y-m-d'));

	$calendar_id   = intval( $_POST['calendar_id'] );
	$bookDate      = strtotime( $_POST['date'] )!==false?date( 'Y-m-d', strtotime( $_POST['date'] ) ):null;
	$time          = intval( $_POST['time'] );
	$name          = sanitize_text_field($_POST['name']);
	$phone         = array_key_exists( 'phone', $_POST ) ? sanitize_text_field($_POST['phone']) : null;
	$email         = sanitize_email( $_POST['email'] );
	$bookExtrasIds = !empty($_POST['extras'])?array_map('intval',$_POST['extras']):array();
	$transaction_started = false;

	try {
		if (empty($name) or empty($email) or empty($bookDate) or strtotime($bookDate) < $today_time)
			throw new Exception("Email or name or book date are invalid. Please type valid values");

		if (!is_email($email))
			throw new Exception("Email is incorrect");

		/* @var GLCalendar\Calendar $calendar */
		$calendar = \GLCalendar\Calendar::getById($calendar_id);
		if (empty($calendar))
			throw new Exception("Calendar not found");

		$calendarExtras = $calendar->getExtras();
		$calendarExtrasIds = array_map(function(\GLCalendar\Extra $e){return $e->id;},$calendarExtras);

		$bookingController = new \GLCalendar\BookingController($calendar);
		$bookingController->loadBookingForDay($bookDate);
		$bookingController->loadUnavailForDay($bookDate);

		$wrongExtrasIds = array_diff($bookExtrasIds, $calendarExtrasIds);
		if (count($wrongExtrasIds) > 0)
			throw new Exception("Wrong extras specified. Please reload the page");

		$bookExtras = array_filter($calendarExtras, function(\GLCalendar\Extra $extra) use ($bookExtrasIds) {
			return in_array($extra->id, $bookExtrasIds);
		});

		$total_amount = \GLCalendar\BookingController::calcPrice($calendar, $bookExtras);

		if ($bookingController->isDayUnavail($bookDate) or
		    !$bookingController->isTimeAvailable($time, $bookDate)) {
			throw new Exception( "Sorry, time is busy, please choose another time.");
		}

		$payment_system = $calendar->getPaymentSystem();
		$payment_handler = null;

		if ($payment_system != null) {
			/**
			 * @var \GLCalendar\PaymentSystem $payment_system
			 */
			$payment_handler = $payment_system->get_handler();
			if ($payment_handler instanceof \GLCalendar\Payment\Gateway\IOnFlyGateway) {
				if (!$payment_handler->is_charge_process($_POST))
					throw new Exception("Wrong token. Please reload the page");
			}
		}

		\GLCalendar\Entity::startTransactionStatic();
		$transaction_started = true;

		$customer = \GLCalendar\Customer::findByEmail($email);
		if (!$customer) {
			$customer = new \GLCalendar\Customer();
			$customer->email = $email;
			$customer->name = $name;
			$customer->phone = $phone;
			$customer->sanitize();
			$customer->validate(true);
			if (!$customer->save()) {
				throw new Exception("Unable to create customer");
			}
		}

		$booking = new \GLCalendar\DateTimeBook();
		$booking->date = $bookDate;
		$booking->time_from = $time;
		$booking->time_to = $time+$calendar->slot_duration;
		$booking->customer_id = $customer->id;
		$booking->calendar_id = $calendar->id;
		$booking->price = $total_amount;
		$booking->sanitize();
		$booking->validate(true);

		$lock_name = "booking_lock_".$calendar_id;

		$booking->lock($lock_name);
		$bookingController->loadBookingForDay($bookDate);
		if ($bookingController->isDayUnavail($bookDate) or
		    !$bookingController->isTimeAvailable($time, $bookDate)) {
			$booking->releaseLock($lock_name);
			throw new Exception( "Sorry, time is busy, please choose another time.");
		}
		if (!$booking->save()) {
			$booking->releaseLock($lock_name);
			throw new Exception("Unable to create booking. ".$booking->save_last_error());
		}
		$booking->releaseLock($lock_name);

		if (!$booking->setExtras($bookExtras))
			throw new Exception("Unable to create booking. ".$booking->save_last_error());

		$transaction = null;
		$gateway_transaction = null;
		if ($payment_handler instanceof \GLCalendar\Payment\Gateway\IOnFlyGateway) {
			$transaction = new GLCalendar\Payment\Transaction();
			$transaction->payment_system_id = $payment_system->id;
			$transaction->booking_id = $booking->id;

			$transaction_class = $payment_handler::TRANSACTION;

			/**
			 * @var \GLCalendar\Payment\Gateway\IOnFlyTransaction $gateway_transaction
			 */
			$gateway_transaction = new $transaction_class;
			$gateway_transaction->setToken($payment_handler->fetch_token_post($_POST));

			$transaction->status = 'process';
			$transaction->amount = $booking->price;
			$transaction->currency = $calendar->currency;
			$transaction->sanitize();
			$transaction->validate(true);
			if (!$transaction->save())
				throw new Exception("Unnable to create booking. Database error. Please try again later");

			$gateway_transaction->attach_to_transaction($transaction);
			$gateway_transaction->sanitize();
			$gateway_transaction->validate(true);
			if (!$gateway_transaction->save())
				throw new Exception("Unnable to create transaction. Database error. Please try again later ".$gateway_transaction->last_error());

			$booking->sanitize();
			$booking->validate(true);
			if (!$booking->save())
				throw new Exception("Unnable to create booking. Please try again later");
		}
		\GLCalendar\Entity::commitTransactionStatic();
		$transaction_started = false;

		if ($payment_handler instanceof \GLCalendar\Payment\Gateway\IOnFlyGateway) {
			$result = $payment_handler->process_payment($_POST, $booking, $calendar);
			if ($result->is_success) {
				$gateway_transaction->setProcessedTokens($result->data);
				$gateway_transaction->sanitize();
				$gateway_transaction->validate(true);
				$gateway_transaction->save();

				$transaction->status = \GLCalendar\Payment\Transaction::STATUS_SUCCESS;
				$transaction->sanitize();
				$transaction->validate(true);
				$transaction->save();
			} else {
				$booking->delete();
				$gateway_transaction->setToken($payment_handler->fetch_token_post($_POST));
				$gateway_transaction->sanitize();
				$gateway_transaction->validate(true);
				$gateway_transaction->save();

				$transaction->status = \GLCalendar\Payment\Transaction::STATUS_FAIL;
				$transaction->fail_reason = $result->fail_reason;
				$transaction->sanitize();
				$transaction->validate(true);
				$transaction->save();
				throw new Exception($result->fail_reason);
			}
		}
		\GLCalendar\EmailNofitication::new_booking($booking, $customer);
		echo json_encode(array('status'=>'success'));
	} catch (Exception $e) {
		if ($transaction_started) \GLCalendar\Entity::rollbackTransactionStatic();
		echo json_encode(array('status'=>'error','reason'=>$e->getMessage()));
	}

	wp_die();
	die;
}

function BKFORB_GCAL_gcal_get_date_info () {
	if(!check_ajax_referer('bkforb_nonce', '_nonce')) {
		header("HTTP/1.1 400 Bad Request");
		wp_die();
		die;
	}
	$now_localtime = new DateTime(null, BKFORB_GCAL_gcal_wp_datetimezone());
	$today_time = strtotime($now_localtime->format('Y-m-d'));  // utc for wp current date

	if (strtotime($_POST['date']) === false) {
		header("HTTP/1.1 400 Bad Request");
		wp_die();
		die;
	}

	$bookDate = date('Y-m-d', strtotime($_POST['date'])); // the bookDate will be in UTC TZ (since WP set the default php timezone to UTC), $_POST['date'] is string in Y-m-d format (also in UTC)
	$calendar_id = intval($_POST['calendar_id']);

	/* @var GLCalendar\Calendar $calendar */
	$calendar = \GLCalendar\Calendar::getById($calendar_id);
	if ($calendar==null) {
		header("HTTP/1.1 400 Bad Request");
		wp_die();
		die;
	}

	$bookingController = new \GLCalendar\BookingController($calendar);
	$bookingController->loadBookingForDay($bookDate);
	$bookingController->loadUnavailForDay($bookDate);

	$no_free_time_text = abfb_gcal_booking_window_unavail($bookDate);

	if (strtotime($bookDate)<$today_time or
		$bookingController->isDayUnavail($bookDate)) {
		echo $no_free_time_text;
		wp_die();die;
	}

	$extras = $calendar->getExtras();
	$dayOptions = array();
	$dayAvailable = false;

	for ($i = $calendar->time_from; $i < $calendar->time_to; $i+=$calendar->slot_duration){
		$d="";
		if (!$bookingController->isTimeAvailable($i,$bookDate))$d="disabled";
		if ($d=="")$dayAvailable=true;

		$dayOptions[] = array(
			'value' => $i,
			'attr' => $d,
			'label' => \GLCalendar\BookingController::formatMinutes($i)." - ".\GLCalendar\BookingController::formatMinutes($i+$calendar->slot_duration)
		);
	}
	if (!$dayAvailable) {
		echo $no_free_time_text;
		wp_die();die;
	}
	echo abfb_gcal_booking_window(
		$bookDate,
		$calendar,
		\GLCalendar\Calendar::$currencyChar[$calendar->currency],
		$extras,
		$dayOptions
	);
	wp_die();
	die;
}

add_filter('wp_ajax_gcal_edit_calendar', 'BKFORB_GCAL_gcal_edit_calendar' );
add_filter('wp_ajax_gcal_book', 'BKFORB_GCAL_gcal_book' );
add_filter('wp_ajax_nopriv_gcal_book', 'BKFORB_GCAL_gcal_book' );
add_filter('wp_ajax_gcal_get_date_info', 'BKFORB_GCAL_gcal_get_date_info' );
add_filter('wp_ajax_nopriv_gcal_get_date_info', 'BKFORB_GCAL_gcal_get_date_info' );

