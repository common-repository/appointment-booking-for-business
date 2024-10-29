<?php

namespace GLCalendar\Exception;


class ValidationNotCalledException extends \Exception{
	public function __construct( $message = "", $code = 0, \Throwable $previous = null ) {
		parent::__construct( "Programming error: validation not called ($message)", $code, $previous );
	}
}