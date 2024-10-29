<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['create'])) {
    include_once ('forms/edit_calendar.php');
    init_calendar_form(null);
} elseif (isset($_GET['view_id'])) {
    include_once ('forms/edit_calendar.php');
    $calendar_id = intval($_GET['view_id']);
    $calendar = \GLCalendar\Calendar::getById($calendar_id);
    if ($calendar!=null){
        init_calendar_form($calendar);
    } else {
        ?>
        <div class="wrap"><h1>Calendar #<?=$calendar_id?> not found</h1></div>
        <?php
    }
} else {

include_once('tables/calendars.php');
$table = new GCal_CalendarsListTable();
?>
<div class="wrap">
	<h1><?=__('Calendars')?></h1>
    <a href="?page=<?=esc_attr($_REQUEST['page'])?>&create">
        <button class="button action">Create calendar</button>
    </a>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
        <?php
            $table->display();
        ?>
    </form>
</div>

<?php } ?>