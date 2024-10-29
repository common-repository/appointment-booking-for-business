<?php

namespace GLCalendar;
defined('ABSPATH') || die('Access Denied');
spl_autoload_register(function ( $className ) {
	if(strpos($className, __NAMESPACE__) === 0) {
		$className = substr($className,strlen(__NAMESPACE__)+1);
		if (empty($className)) return;
		$classPath = str_replace("\\",DIRECTORY_SEPARATOR,$className);
		$filename = __DIR__ . DIRECTORY_SEPARATOR . "class" . DIRECTORY_SEPARATOR . $classPath . ".php";
		if (is_readable($filename) && file_exists($filename))
		{
			require_once $filename;
		}
	}
});