<?php

defined('ABSPATH') || die('Access Denied');

function gcal_notif_admin_new_booking($date, $time_from, $time_to, $username, $useremail, $userphone, $site_name, $site_url) {
	$time_from = date("h:iA", strtotime($date)+$time_from*60);
	$time_to = date("h:iA", strtotime($date)+$time_to*60);
	$date = date("F jS, Y",strtotime($date));
	ob_start();?>
    Hello, <br/>
    <br/>
    You have a new appointment: <br/>
    <br/>
    <b><?=$time_from?> to <?=$time_to?></b> on <b><?=$date?></b> <br/><br/>
    <b>Customer data:</b> <br/>
    Name: <?=esc_html($username)?> <br/>
    Email: <?=esc_html($useremail)?> <br/>
    <?=!empty($userphone)?"Phone: ".esc_html($userphone)."<br/>":""?>
    <br/>
    Thank you, <br/>
	<?=$site_name?> <br/>
    <br/>
	<?=$site_url?> <br/>
	<?php return ob_get_clean();
}

function gcal_notif_user_booking_notification ($date, $time_from, $time_to, $username, $site_name, $site_url) {
    $time_from = date("h:iA", strtotime($date)+$time_from*60);
    $time_to = date("h:iA", strtotime($date)+$time_to*60);
    $date = date("F jS, Y",strtotime($date));
    ob_start();?>
    Hello <?=esc_html($username)?>, <br/>
    <br/>
    Your appointment has been confirmed for: <br/>
    <br/>
    <b><?=$time_from?> to <?=$time_to?></b> on <b><?=$date?></b> <br/>
    <br/>
    Thank you, <br/>
    <?=$site_name?> <br/>
    <br/>
    <?=$site_url?> <br/>
	<?php return ob_get_clean();
}

function gcal_notif_booking_canceled ($date, $time_from, $time_to, $username, $site_name, $site_url) {
	$time_from = date("h:iA", strtotime($date)+$time_from*60);
	$time_to = date("h:iA", strtotime($date)+$time_to*60);
	$date = date("F jS, Y",strtotime($date));
	ob_start();?>
    Hello <?=esc_html($username)?>, <br/>
    <br/>
    Your appointment on: <br/>
    <br/>
    <b><?=$time_from?> to <?=$time_to?></b> on <b><?=$date?></b> <br/>
    <br/>
    Has been cancelled. Please contact us if you have any questions.<br/>
    <br/>
    Thank you, <br/>
	<?=$site_name?> <br/>
    <br/>
	<?=$site_url?> <br/>
	<?php return ob_get_clean();
}
