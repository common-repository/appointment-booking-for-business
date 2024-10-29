<?php
defined('ABSPATH') || die('Access Denied');
function init_calendar_form(\GLCalendar\Calendar $calendar = null) {
    $extras = \GLCalendar\Extra::getList();

    $selections = array();
    $slot_duration = 60;
    $inactive_intervals = array();

    function minutes_to_time($mins) {
        return \GLCalendar\BookingController::formatMinutes($mins);
    }

    $calendar_extras_ids = [];
    $payment_system_id = 0;
    if ($calendar!=null) {
        $inactive_intervals = $calendar->getInactivityTimes();
        $calendar_extras_ids = array_map(function(\GLCalendar\Extra $c){return $c->id;}, $calendar->getExtras());

        $firstDay = \GLCalendar\BookingController::firstDayOfMonth(time());
        $added2Months = \GLCalendar\BookingController::dateAddMonths($firstDay, 12);
        $selections = $calendar->getUnavailDays($firstDay, $added2Months);

        $slot_duration = $calendar->slot_duration;

        $ps = $calendar->getPaymentSystem();
        if ($ps) $payment_system_id = $ps->id;
    }
?>
<link href="<?=plugin_dir_url(__DIR__)?>lib/chosen_v1.8.7/chosen.min.css" rel="stylesheet" type="text/css" />
<script src="<?=plugin_dir_url(__DIR__)?>lib/chosen_v1.8.7/chosen.jquery.min.js"></script>
<script src="<?=plugin_dir_url(__DIR__)?>lib/ajax-chosen.js"></script>

<div class="wrap">
    <div class="resp-ajax"></div>
	<h1><?=$calendar==null?__('Create calendar'):__('Edit calendar')?></h1>
	<form method="post" action="" class="edit-calendar-form">
		<table class="form-table">
            <?php if ($calendar!=null) { ?>
            <tr>
                <th>ID: <?=$calendar->id?></th>
                <td>[<?=BKFORB_GCAL_PLUGIN_SHORTCODE?> id=<?=$calendar->id?>]</td>
            </tr>
            <?php } ?>
			<tr>
				<th><?=__('Calendar name')?></th>
				<td>
					<input type="text"
						   name="calendar_name"
					       class="regular-text"
					       required
                           value="<?=$calendar==null?'':esc_html($calendar->name)?>"
                    />
				</td>
			</tr>
            <tr>
                <th><?=__('Linked extras')?></th>
                <td>
                    <select multiple="multiple" 
                            class="chosen-select" 
                            name="extras[]"
                            id="extras"
                            data-placeholder="Start typing the name"
                            >
                        <?php
                        /** @var GLCalendar\Extra $e */
                        foreach ($extras as $e) {
                            $selected = '';
                            if ($calendar!=null and in_array($e->id, $calendar_extras_ids))
                                $selected = 'selected';
                            ?>
                        <option value="<?=$e->id?>" <?=$selected?>><?=esc_html($e->name)?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?=__('Slot duration')?></th>
                <td>
                    <select name="slot_duration">
                        <?php
                        foreach (array(15,20,30,60) as $v) {
                            $selected = '';
                            if ($calendar!=null and $calendar->slot_duration==$v)
                                $selected = 'selected';
                            ?>
                            <option value="<?=$v?>" <?=$selected?>><?=$v?></option>
                        <?php
                        }
                        ?>
                    </select>
                    mins
                </td>
            </tr>
            <tr>
                <th><?=__('Slot price')?></th>
                <td>
                    <input type="number"
                           name="price"
                           class="regular-text"
                           style="width: 100px;display: inline;"
                           value="<?=$calendar==null?'':$calendar->price?>"
                    />
                    <select name="currency" style="width: 100px;display: inline;">
                        <?php foreach (\GLCalendar\Calendar::$allowedCurrencies as $v) {
                            $selected = $v==$calendar->currency?'selected':'';
                            ?>
                        <option value="<?=$v?>" <?=$selected?>><?=\GLCalendar\Calendar::$currencyChar[$v]?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?=__('Available Times')?></th>
                <td>
                    From:
                    <select name="time_from" required>
                        <?php for($i=0; $i<24*60; $i+=$slot_duration) {
                            $selected = '';
                            if ($calendar!=null and $calendar->time_from==$i)
                                $selected = 'selected';
                            ?>
                            <option value="<?=$i?>" <?=$selected?>><?=minutes_to_time($i)?></option>
                        <?php } ?>
                    </select>
                    To:
                    <select name="time_to" required>
	                    <?php for($i=0; $i<24*60; $i+=$slot_duration) {
	                        $selected = '';
		                    if ($calendar!=null and $calendar->time_to==$i)
			                    $selected = 'selected';
	                        ?>
                            <option value="<?=$i?>" <?=$selected?>><?=minutes_to_time($i)?></option>
	                    <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?=__('Unavailable Times')?></th>
                <td>
                    <div class="inactive-times-container"></div>
                    <button class="add-inactive-interval" type="button">Add</button>
                    <div class="inactive-time-row-template" style="display: none;">
                        From:
                        <select name="inactive_time_from_" required>
		                    <?php for($i=0; $i<24*60; $i+=$slot_duration) {
			                    ?>
                                <option value="<?=$i?>"><?=minutes_to_time($i)?></option>
		                    <?php } ?>
                        </select>
                        To:
                        <select name="inactive_time_to_" required>
		                    <?php for($i=0; $i<24*60; $i+=$slot_duration) {
			                    ?>
                                <option value="<?=$i?>"><?=minutes_to_time($i)?></option>
		                    <?php } ?>
                        </select>
                        <button type="button" class="remove-inactive">Remove</button>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?=__('Payment Gateway')?></th>
                <td>
                    <select name="payment_system" required class="regular-text">
                        <option value="0">Without online payment</option>
		                <?php foreach (\GLCalendar\PaymentSystem::get_all() as $ps) {
			                $selected = '';
			                if ($calendar!=null and $payment_system_id==$ps->id)
				                $selected = 'selected';
			                ?>
                            <option value="<?=$ps->id?>" <?=$selected?>><?=$ps->name?></option>
		                <?php } ?>
                    </select>
                </td>
            </tr>
		</table>
        <h3>Block Unavailable Dates</h3>
        <div class="calendar-container"></div>
		<p class="submit">
			<input type="submit" name="submit"
			       class="button button-primary"
			       value="<?=$calendar==null?__('Create'):__('Save')?>" />
		</p>
	</form>
</div>
<script>
    (function($){
        $(document).ready(function(){
            $("#extras").chosen();

            initInactiveIntervals(<?=json_encode($inactive_intervals)?>);

            $("[name=time_from]").on("change", function(){
                var val = $(this).val()<<0;
                $("[name=time_to] option").each(function(){
                    if ($(this).val()<<0>val)
                        $(this).removeAttr('disabled');
                    else
                        $(this).attr('disabled','disabled');
                });
            });

            var getSelections = printCalendar($('.calendar-container'),[
                <?php
                foreach ($selections as $v) {
	                echo "[dateFromString('{$v['date_from']}'), dateFromString('{$v['date_to']}')], ";
                }
                ?>
            ]);
            window.getSelections=getSelections;

            $(".edit-calendar-form").on('submit',function(e){
                e.stopPropagation();
                var form = $(this);
                var submit_button = form.find('[type=submit]'),
                    smb_value = submit_button.val();

                var selections = getSelections(),
                    calendar_id = <?=($calendar!=null?$calendar->id:0)?>;
                var old_slot_duration = <?=($calendar!=null?$calendar->slot_duration:0)?>;

                submit_button.val('<?=__('Wait')?>').attr('disabled',true);
                editCalendar(calendar_id, new FormData(form.get(0)), selections)
                    .then(function(resp){
                        submit_button.val(smb_value).removeAttr('disabled');
                        $("html, body").animate({"scrollTop":0},500);
                        var h = "<div class='notice notice-success is-disimissible'>"+
                            "<p>Saved!</p>"+
                        "</div>";
                        $(".resp-ajax").html(h);

                        var new_loc = '?page=<?=esc_js($_REQUEST['page'])?>&view_id=';
                        if (calendar_id===0 || old_slot_duration != $("[name=slot_duration]").val()){
                            window.location.href = new_loc+resp['calendar_id'];
                        }
                    })
                    .catch(function(e){
                        submit_button.val(smb_value).removeAttr('disabled');
                        $("html, body").animate({"scrollTop":0},500);
                        var h = "<div class='notice notice-error is-disimissible'>"+
                            "<p>Unable to create/edit the calendar. Error: "+e+"</p>"+
                        "</div>";
                        $(".resp-ajax").html(h);
                    });

                return false;
            });
        });
    })(jQuery);
</script>

<?php } ?>