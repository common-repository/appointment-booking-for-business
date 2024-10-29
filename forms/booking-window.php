<?php

defined('ABSPATH') || die('Access Denied');

function abfb_gcal_booking_window_unavail ($bookDate) {
	return "<div class='window-close tablet'><i class='fa fa-times'></i></div>
		<div class='window-close mobile'><i class='fa fa-arrow-left'></i></div>
		<div class='window-header'>No free time at $bookDate</div>
		Sorry, there is no free time for this date. Choose another day.";
}

function abfb_gcal_booking_window ($bookDate, \GLCalendar\Calendar $calendar, $currencyChar, $extras, $dayOptions) {
	ob_start();

	$extrasStructure = "";

	/* @var GLCalendar\Extra $extra */
	foreach ($extras as $extra) {
	$extrasStructure .= "<label class='extra-row'>
		<div class='cb-block'>
			<input type='checkbox' value={$extra->id} name='extras[]' class='extra-checkbox' data-price='{$extra->price}' data-sum-op='{$extra->sum_op}'/>
		</div>
		<div class='name-block'>
			<span>".esc_html($extra->name)."</span>
		</div>
		<div class='price-block'>
			<b>+ ".($extra->sum_op=='+'?$currencyChar:'').round($extra->price,2).($extra->sum_op=='%'?'%':'')."</b>
		</div>
		<i class='clear'></i>
	</label>";
	}


	$dayOptionsStructure = "";
	foreach ($dayOptions as $option) {
        $dayOptionsStructure .= "<option value='{$option['value']}' {$option['attr']}>{$option['label']}</option>";
	}
	?>
	<form action='#' class='window-booking-form'>
		<input type='hidden' name='price' value='<?=$calendar->price?>' />
		<input type='hidden' name='currency' value='<?=$calendar->currency?>' />
		<input type='hidden' name='book_date' value='<?=$bookDate?>' />
		<div class='window-close tablet'><i class='fa fa-times'></i></div>
		<div class='window-close mobile'><i class='fa fa-arrow-left'></i></div>
		<div class='window-header'>Booking <span class='booking-day'></span></div>
		<div class='thank-you'>
			Thank you! <br />
			Your booking has been confirmed <br />
			Check your email inbox
		</div>
		<div class='form-error'></div>
		<div class='form-row'>
			<label>Name</label>
			<span>
				<input type='text' name='name' required/>
			</span>
		</div>
		<div class='form-row'>
			<label>Email</label>
			<span>
				<input type='email' name='email' required/>
			</span>
		</div>
		<div class='form-row'>
			<label>Phone</label>
			<span>
				<input type='text' name='phone' />
			</span>
		</div>
		<div class='form-row'>
			<label>Time</label>
			<span>
				<select name='time'>
					<?=$dayOptionsStructure?>
				</select>
			</span>
		</div>

		<?php if (!empty($extrasStructure)) { ?>
		<div class='form-row extras-row'>
			<label class='extras-container-label'>Extras</label>
			<span>
				<div class='extras-container'>
					<?=$extrasStructure?>
				</div>
			</span>
		</div>
		<?php } ?>

		<?php if (!empty(floatval($calendar->price)) or !empty($extrasStructure)) { ?>
		<div class='form-row'>
			<label>Price</label>
			<span class='price-container'><?=$currencyChar?><span class='price'></span></span>
		</div>
		<?php } ?>

		<div class='form-row bottom-button submit-button-container'>
			<label></label>
			<span>
				<button type='submit'>Book Now</button>
			</span>
		</div>
	</form>
	<?php
	return ob_get_clean();
}