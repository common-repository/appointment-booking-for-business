<?php

namespace GLCalendar\Exception;


class SanitizationNotCalledException extends \Exception{
	public function __construct( $message = "", $code = 0, \Throwable $previous = null ) {
		parent::__construct( "Programming error: sanitization not called ($message)", $code, $previous );
	}
}