<?php
defined('ABSPATH') || die('Access Denied');
function init_extra_form(\GLCalendar\Extra $extra = null) {
    global $wpdb;

	if (!empty($_POST)) {
	    if (!check_admin_referer('bkforb_nonce_admin', '_nonce')) {
		    echo "<div class='notice notice-error is-disimissible'>
                <p>Unable to create/edit the extra. (Nonce error)</p>
                </div>";
        } else {
		    $new_extra         = $extra == null ? new GLCalendar\Extra() : $extra;
		    $new_extra->name   = sanitize_text_field($_POST['extra_name']);
		    $new_extra->price  = floatval($_POST['price']);
		    $new_extra->sum_op = sanitize_text_field($_POST['sum_op']);
		    try {
                if (empty($new_extra->name))
                    throw new Exception("Name is empty");

                if (!in_array($new_extra->sum_op, array('%','+')))
                    throw new Exception("Wrong sum operator");

                $new_extra->sanitize();
                $new_extra->validate(true);

			    if ( $new_extra->save() ) {
				    $new_loc = '?page=' . esc_js($_REQUEST['page']) . '&view_id=' . $new_extra->id;
				    ?>
                    <script>window.location.href = '<?=$new_loc?>';</script><?php
			    } else {
				    throw new Exception($wpdb->last_error);
			    }
            } catch (Exception $e) {
			    echo "<div class='notice notice-error is-disimissible'>
                    <p>Unable to create/edit the extra. Errors: {$e->getMessage()}</p>
                    </div>";
            }
	    }
    }
?>

<div class="wrap">
	<h1><?=$extra==null?__('Create Extra'):__('Edit Extra')?></h1>
	<form method="post" action="">
		<table class="form-table">
            <?php if ($extra!=null) { ?>
            <tr>
                <th>ID: <?=$extra->id?></th>
                <td></td>
            </tr>
            <?php } ?>
			<tr>
				<th><?=__('Extra name')?></th>
				<td>
					<input type="text"
						   name="extra_name"
					       class="regular-text"
					       required
                           value="<?=$extra==null?'':esc_html($extra->name)?>"
                    />
				</td>
			</tr>
            <tr>
                <th><?=__('Price')?></th>
                <td>
                    <input type="text"
                           name="price"
                           class="regular-text"
                           required
                           value="<?=$extra==null?'':$extra->price?>"
                    />
                </td>
            </tr>
            <tr>
                <th><?=__('Operation')?></th>
                <td class="extra-operations">
                    <?php foreach (array('+','%') as $op) { ?>
                        <input type="radio"
                               name="sum_op"
                               class="regular-text"
                               id="op_<?=$op?>"
                               required
                               value="<?=$op?>"
                               <?=($extra!=null and $extra->sum_op==$op)?'checked':''?>
                        />
                        <label for="op_<?=$op?>"><?=$op?></label>
                    <?php } ?>
                </td>
            </tr>
		</table>
		<p class="submit">
            <input type="hidden" name="_nonce" value="<?=wp_create_nonce( 'bkforb_nonce_admin' )?>" />
			<input type="submit" name="submit"
			       class="button button-primary"
			       value="<?=$extra==null?__('Create'):__('Save')?>" />
		</p>
	</form>
</div>

<?php } ?>