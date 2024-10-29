<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');

class BookingController {
	/**
	 * @var Calendar
	 */
	private $calendar;

	private $inactive_times = array();

	public function __construct(Calendar $calendar) {
		$this->calendar = $calendar;
		$this->inactive_times = $this->calendar->getInactivityTimes();
	}

	public function isTimeInactive($time) {
		foreach ($this->inactive_times as $v) {
			if ($time>=$v[0] and $time<$v[1]) return true;
		}
		return false;
	}

	public $calendar_unavails = array();
	private $booking_data = array();
	private function loadBooking($cond) {
		global $wpdb;
		$res = $wpdb->get_results("
			SELECT b.id, b.date, b.time_from, b.time_to, b.customer_id, b.calendar_id, b.created_at 
			FROM ".DateTimeBook::get_table_static()." b 
			WHERE $cond and b.calendar_id = {$this->calendar->id}
			ORDER BY b.time_from ASC 
		");
		foreach ($res as $r) {
			if (!array_key_exists($r->date,$this->booking_data))
				$this->booking_data[$r->date] = array();

			$this->booking_data[$r->date][] = array(
				$r->time_from,
				$r->time_to
			);
		}
		return true;
	}

	/**
	 * @param $time int minutes in the date
	 * @param $date string the date string in Y-m-d format
	 *
	 * @return bool
	 */
	public function isTimeAvailable($time, $date) {
		$time = intval($time);
		if (array_key_exists($date, $this->booking_data)) {
			foreach ($this->booking_data[$date] as $v) {
				if ($time >= $v[0] and $time < $v[1]) return false;
			}
		}

		$slot_start_dt = new \DateTime($date, BKFORB_GCAL_gcal_wp_datetimezone());
		$now_dt = new \DateTime(null, BKFORB_GCAL_gcal_wp_datetimezone());

		if ($slot_start_dt->format('Y-m-d')==$now_dt->format('Y-m-d')){
			$t = $time-$this->calendar->slot_duration-60;
			if ($t<0)$t=0;
			$slot_start_dt->add(new \DateInterval("PT{$t}M"));
			if ($slot_start_dt <= $now_dt)
				return false;
		}

		if (!($time >= $this->calendar->time_from and $time <= $this->calendar->time_to)) return false;

		if ($this->isTimeInactive($time))return false;

		return true;
	}
	public function countFreeSlots($date) {
		$cnt = 0;
		for($i=$this->calendar->time_from; $i<$this->calendar->time_to;$i+=$this->calendar->slot_duration){
			$cnt += intval($this->isTimeAvailable($i, $date));
		}
		return $cnt;
	}
	public function loadBookingForDay($date) {
		$date = date("Y-m-d",strtotime($date));
		return $this->loadBooking("date='$date'");
	}
	public function loadBookingForMonth ($month, $year) {
		$month = intval($month);
		$year = intval($year);
		return $this->loadBooking("MONTH(date)=$month and YEAR(date)=$year");
	}
	public function loadBookingSeveralMonth($month) {
		$month = intval($month);
		return $this->loadBooking("date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND DATE_ADD(NOW(),INTERVAL $month MONTH)");
	}
	public function loadUnavailForPeriod($from, $to) {
		$this->calendar_unavails = array_map(function($d){
			return array(strtotime($d['date_from']), strtotime($d['date_to']));
		}, $this->calendar->getUnavailDays($from, $to));
	}
	public function loadUnavailSeveralMonth($month) {
		$firstDay = self::firstDayOfMonth(time());
		$added2Months = self::dateAddMonths($firstDay, $month);
		$this->loadUnavailForPeriod($firstDay, $added2Months);
	}
	public function loadUnavailForDay ($date) {
		$t = strtotime($date);
		$from = date('Y-m-d', $t - 60*60*25);
		$to = date('Y-m-d', $t + 60*60*25);
		$this->loadUnavailForPeriod($from, $to);
	}
	public function isDayUnavail($date) {
		$dayTime = strtotime($date);
		foreach ($this->calendar_unavails as $d) {
			if ($dayTime>=$d[0] and $dayTime<=$d[1])return true;
		}
		return false;
	}
	static public function firstDayOfMonth ($date) {
		if (is_string($date))
			$date = strtotime($date);
		return date("Y-m-01",$date);
	}
	static public function dateAddMonths ($date, $months) {
		if (is_string($date))
			$date = strtotime($date);
		return date("Y-m-d",strtotime("+$months month",$date));
	}
	static public function formatMinutes($mins) {
		return floor($mins/60).':'.str_pad($mins%60,2,"0",STR_PAD_LEFT);
	}

	/**
	 * @param Calendar $calendar
	 * @param Extra[] $extras
	 */
	static public function calcPrice (Calendar $calendar, $extras) {
		uasort($extras, function($a,$b){
			if ($a->sum_op == $b->sum_op) {
				return 0;
			}
			return ($a->sum_op < $b->sum_op) ? 1 : -1;
		});
		$price = $calendar->price;
		foreach ($extras as $e) {
			if ($e->sum_op=='+')
				$price+=$e->price;
			elseif ($e->sum_op=='%')
				$price*=1+($e->price/100);
		}
		return round($price, 2);
	}
}