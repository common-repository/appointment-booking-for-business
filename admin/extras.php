<?php
defined('ABSPATH') || die('Access Denied');

if (isset($_GET['create'])) {
	include_once ('forms/edit_extra.php');
	init_extra_form(null);
} elseif (isset($_GET['view_id'])) {
	include_once ('forms/edit_extra.php');
	$extra_id = intval($_GET['view_id']);
	$extra = \GLCalendar\Extra::getById($extra_id);
	if ($extra!=null){
		init_extra_form($extra);
	} else {
		?>
        <div class="wrap"><h1>Extra #<?=$extra_id?> not found</h1></div>
		<?php
	}
} else {
include_once('tables/extras.php');
$table = new GCal_ExtrasListTable();
?>
<div class="wrap">
    <h1><?=__('Extras')?></h1>
    <a href="?page=<?=esc_attr($_REQUEST['page'])?>&create">
        <button class="button action">Create extra</button>
    </a>
    <form id="gcal-fitler" method="get">
        <input type="hidden" name="page" value="<?=esc_attr($_REQUEST['page'])?>" />
		<?php
		$table->display();
		?>
    </form>
</div>
<?php } ?>