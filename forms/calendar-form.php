<?php
defined('ABSPATH') || die('Access Denied');
/**
 * Returns table structure for given month and year
 *
 * @param GLCalendar\Calendar $calendar
 * @param GLCalendar\Extra[] $extras
 * @param $month
 * @param $year
 *
 * @return string
 */
function gcal_build_calendar(\GLCalendar\BookingController $bookingController, \GLCalendar\Calendar $calendar, array $extras, $month, $year, $num) {
	$start_date = strtotime("$year-$month-01");
	$end_date = strtotime("+1 month", $start_date)-(60*60*24);
	$weekday = date('w', $start_date);
	$now_localtime = new DateTime(null, BKFORB_GCAL_gcal_wp_datetimezone());
	$today_time = strtotime($now_localtime->format('Y-m-d'));
	$weekdays = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
	$month_names = array("January", "February", "March", "April", "May", "June",
		"July", "August", "September", "October", "November", "December");

	$month_name = $month_names[$month-1];

	$calendar = "<div class='calendar-wrapper ".($num==0?"active":"")."' data-num='$num'>".
	            "
				<div class='window-bg'></div>
				<div class='window-booking'>
					
				</div>
				<div class='calendar-header'>
					$month_name, $year
					<div class='month-switcher'>
						<i class='fa fa-angle-left switch-month switch-left'></i>
						<span>$month_name</span>
						<i class='fa fa-angle-right switch-month switch-right'></i>
					</div>
				</div>";

	$calendar .= "<table class='calendar' cellpadding=0 cellspacing=0>";
	$calendar .= "<tr>";
	$calendar .= implode("",array_map(function($d){return "<th>$d</th>";}, $weekdays));
	$calendar .= "</tr><tr>";
	if ($weekday > 0) {
		$calendar .= "<td colspan='$weekday'>&nbsp;</td>";
	}

	$date_slots = array();

	for ($i = 1; $i <= date('j',$end_date); $i++) {
		$day_time = strtotime("{$year}-{$month}-{$i}");
		$date_str = date("Y-m-d", $day_time);

		$day_class = 'inactive';
		$date_slots[$date_str] = 0;
		if ($day_time >= $today_time and !$bookingController->isDayUnavail($date_str))
			$day_class = 'day';

		$free_slots = "";
		if ($day_class == 'day') {
			$cnt = $bookingController->countFreeSlots($date_str);
			if ($cnt > 0)
				$free_slots = "<div class='free-slots'><span>{$cnt}</span> slots free</div>";
			else {
				$day_class = 'inactive';
			}
			$date_slots[$date_str] = $cnt>=0?$cnt:0;
		}

		$calendar .= "<td class='$day_class' rel='$date_str'>".
		             "<div class='col-inner'>
						<div class='caption'>$i</div>
						$free_slots
					</div>
					</td>";

		if (++$weekday>6 and $i != date('j',$end_date)) {
			$calendar .= '</tr><tr>';
			$weekday = 0;
		}
	}
	if ($weekday > 0 and $weekday < 6)
		$calendar .= "<td colspan='".(6-$weekday+1)."'></td>";
	$calendar .= "</tr></table>";

	$calendar .= "<div class='calendar-mobile'>".
				 "<div class='weeks'>";

	$last_month_day = date('j',$end_date);
	for ($i = 0; $i < ceil($last_month_day)/7; $i++) {
		$week_start = $i*7+1;
		$week_end = min(($i+1)*7, $last_month_day);

		$calendar .= "<div class='week ".($i==0?'active':'')."' data-week=$i>".
		             "<div class='week-inner'>".
		             "<div class='week-header'>".
		             "<i class='fa fa-caret-left switch-week switch-week-left'></i>".
		             "$month_name " . BKFORB_GCAL_gcal_num_2_places($week_start) . " - " . BKFORB_GCAL_gcal_num_2_places($week_end) .
		             "<i class='fa fa-caret-right switch-week switch-week-right'></i>".
		             "</div>"; // week-header end
		for ($k = $week_start; $k <= $week_end; $k++) {
			$day_time = strtotime("{$year}-{$month}-{$k}");
			$date_str = date("Y-m-d", $day_time);
			$slots = $date_slots[$date_str];
			$add_class = "";
			if ($slots == 0) $add_class = 'inactive';
			$calendar .= "<div class='week-day $add_class' rel='$date_str'>".
			             "<div class='day-num'>".substr($month_name,0,3)."<b>" . BKFORB_GCAL_gcal_num_2_places($k) . "</b></div>" .
			             "<div class='slots-info'>";

			if ($slots == 0) {
				$calendar .= "<span class='free-slots'>No slots available</span>";
			} else {
				$calendar .= "<div class='select-time'>Select time <i class='fa fa-angle-right'></i></div>" .
				" <span class='free-slots'>Slots: $slots</span>";
			}
            $calendar .= "</div>". // slots-info end
			             "<div class='clear'></div>".
			             "</div>"; // week-day end
		}

		$calendar .= "<div class='week-bottom'>".
		             ($i!=0?"<span class='switch-week switch-week-left'><span>&laquo;</span> previous week</span>":"").
		             "<span class='switch-week switch-week-right'>next week <span>&raquo;</span></span>".
		             "<div class='clear'></div>".
		             "</div>";

		$calendar .= "</div>". // week-inner end
		             "</div>"; // week end
	}

	$calendar .= "</div>"; // weeks end

	$calendar .= "</div>"; // calendar-mobile end
	$calendar .= "</div>"; // calendar-wrapper end
	return $calendar;
}

function gcal_create_calendar_form (\GLCalendar\Calendar $calendar) {
	$extras = $calendar->getExtras();
	$bookingController = new \GLCalendar\BookingController($calendar);
	$bookingController->loadBookingSeveralMonth(6);
	$bookingController->loadUnavailSeveralMonth(6);
	$cont = "<div class='calendar-container' data-calendar-id='{$calendar->id}'>";
	for($i = 0; $i < 5; $i++) {
		$dateComponents = getdate(strtotime("+$i month", time()));
		$month          = $dateComponents['mon'];
		$year           = $dateComponents['year'];

		$cont .= gcal_build_calendar( $bookingController, $calendar, $extras, $month, $year, $i );
	}
	$cont .= "</div>";
	return $cont;
}
