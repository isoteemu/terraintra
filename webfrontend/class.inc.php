<?php

/**
 * Autoloader for intra classes
 */
function intra_api_loadclass($class) {

	if(class_exists($class, false) || interface_exists($class, false)) {
		return;
	}

	$dir = dirname(__FILE__).'/lib/';

	$classify = explode('_', $class);
	$classify = array_map('intra_api_loadclass_camelize', $classify);
	$classfile = $dir.implode(DIRECTORY_SEPARATOR, $classify).'.php';

	$file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

	if(file_exists($dir.$file))
		include_once($dir.$file);
	elseif(file_exists($classfile))
		include_once($classfile);
	else
		@include_once($file);

	if(!class_exists($class, false) && !interface_exists($class, false)) {
		//throw new RuntimeException('Could not load class '.$class);
		return false;
	}
}

function intra_api_loadclass_camelize($word) {
	return str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9]+/',' ',$word)));
}

/**
 * Shorthand function to perform singleton -style calls to Intra_CMS class.
 */
if(!function_exists('Intra_CMS')) {
	function &Intra_CMS() {
		static $cms;
		if(!$cms) {
			$cms = Intra_CMS::factory(Intra_CMS::DETECT);
		}
		return $cms;
	}
}

// Register autoloader
spl_autoload_register('intra_api_loadclass');
ini_set('log_errors_max_len', 0);
ini_set('display_errors', 1);
