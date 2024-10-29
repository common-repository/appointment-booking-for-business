<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

class EmailNofitication {
	static public function booking_deleted(DateTimeBook $book, Customer $customer) {
		include_once (implode(DIRECTORY_SEPARATOR,array(__DIR__,"..","emails/notifications.php")));

		wp_mail($customer->email,"Your appointment for ".date('m/d/Y',strtotime($book->date))." has been cancelled",
			gcal_notif_booking_canceled(
				date('Y-m-d',strtotime($book->date)),
				$book->time_from,
				$book->time_to,
				$customer->name,
				get_bloginfo('name'),
				get_bloginfo('url')
			),
			array('Content-Type: text/html; charset=UTF-8',"From: ".get_bloginfo('name')." <".get_option('admin_email').">")
		);
	}
	static public function new_booking (DateTimeBook $book, Customer $customer) {
		include_once (implode(DIRECTORY_SEPARATOR,array(__DIR__,"..","emails/notifications.php")));

		wp_mail($customer->email, "Your appointment for ".date('m/d/Y',strtotime($book->date))." has been confirmed",
			gcal_notif_user_booking_notification(
				date('Y-m-d',strtotime($book->date)),
				$book->time_from,
				$book->time_to,
				$customer->name,
				get_bloginfo('name'),
				get_bloginfo('url')
			),
			array('Content-Type: text/html; charset=UTF-8',"From: ".get_bloginfo('name')." <".get_option('admin_email').">")
		);

		wp_mail(get_option('admin_email'),"New appointment for ".date('m/d/Y',strtotime($book->date))."",
			gcal_notif_admin_new_booking(
				date('Y-m-d',strtotime($book->date)),
				$book->time_from,
				$book->time_to,
				$customer->name,
				$customer->email,
				$customer->phone,
				get_bloginfo('name'),
				get_bloginfo('url')
			),
			array('Content-Type: text/html; charset=UTF-8',"From: ".get_bloginfo('name')." <".get_option('admin_email').">")
		);
	}
}