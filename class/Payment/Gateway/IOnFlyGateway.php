<?php

namespace GLCalendar\Payment\Gateway;


use GLCalendar\IEntity;

interface IOnFlyGateway extends IEntity {
	public function is_charge_process( $post );
	public function fetch_token_post ($post);
}