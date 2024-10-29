<?php
defined('ABSPATH') || die('Access Denied');
function init_customer_form (\GLCalendar\Customer $customer) { ?>
	<div class="wrap">
		<h1><?=__("View customer #{$customer->id}")?></h1>
		<table class="form-table">
			<tr>
				<th>ID</th>
				<td><?=$customer->id?></td>
			</tr>
			<tr>
				<th>Name</th>
				<td><?=esc_html($customer->name)?></td>
			</tr>
			<tr>
				<th>Email</th>
				<td><?=esc_html($customer->email)?></td>
			</tr>
            <tr>
                <th>Phone</th>
                <td><?=esc_html($customer->phone)?></td>
            </tr>
			<tr>
				<th>Registration</th>
				<td><?=$customer->created_at?></td>
			</tr>
		</table>
	</div>
<?php
}
?>

