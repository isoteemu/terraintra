<?php
/**
 * Init functions for intra api
 */

function intra_api_init_includepath() {
	// Set include path
	set_include_path(
		get_include_path()
		.PATH_SEPARATOR.dirname(__FILE__).'/../lib'
		.PATH_SEPARATOR.dirname(__FILE__).'/../external/Pear'
	);
}

function intra_api_init_autoloader() {
	// Register autoloader
	spl_autoload_register('intra_api_loadclass');
}

function intra_api_init_database() {
	global $db_url;
	// Connect intra classes into DB
	Intra_Helper::db(variable_get('terra_intra_db', $db_url));
}

function intra_api_init_style() {

	$path = drupal_get_path('module', 'intra_api');
	drupal_add_css($path.'/css/intra-api.css');
	drupal_add_css($path.'/css/vcard-details.css');
	drupal_add_css($path.'/css/infobox.css');

	drupal_add_js("$path/js/Intra.js");

	if(module_exists('intra_activity')) {
		drupal_add_css(drupal_get_path('module', 'intra_activity').'/css/activity.css');
	}

	// Add semantic javascript handlers
	drupal_add_js("$path/js/microformat.js");
	if(module_exists('intra_company')) {
		drupal_add_js(drupal_get_path('module', 'intra_company').'/js/microformat.js');
	}
	if(module_exists('intra_contact')) {
		drupal_add_js(drupal_get_path('module', 'intra_contact').'/js/microformat.js');
	}

	drupal_add_js($path.'/js/jquery/qtip/jquery.qtip-1.0.0-rc3.js');

	drupal_add_js("$path/js/Intra.Ui.Tooltip.js");

	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		jquery_ui_add(array('ui.core'));
		drupal_add_js($path.'/js/Intra.Ui.Buttons.js');
		
	}

	drupal_add_js("$path/js/Intra.Print.js");
}

/**
 * Autoloader for intra classes
 */
function intra_api_loadclass($class) {

	if(class_exists($class, false) || interface_exists($class, false)) {
		return;
	}

	$classify = explode('_', $class);
	$classify = array_map('intra_api_loadclass_camelize', $classify);
	$classfile = implode(DIRECTORY_SEPARATOR, $classify).'.php';

	$file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

	if(file_exists(dirname(__FILE__).'/lib/'.$classfile)) {
		include_once($classfile);
	} else {

		if(!include_once($file))
			@include_once($classfile);

	}
	
	if(!class_exists($class, false) && !interface_exists($class, false)) {
		if(function_exists('dfb')) dfb('Could not load class '.$class);
		return false;
	}
}

function intra_api_loadclass_camelize($word) {
	return str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9]+/',' ',$word)));
}

/**
 * Cross-platform CMS stuff.
 */
if(!function_exists('Intra_CMS')) {
	function &Intra_CMS() {
		static $cms;
		if(!$cms) {
			@include_once(dirname(__FILE__).'/lib/Intra/CMS.php');
			$cms = Intra_CMS::factory(Intra_CMS::DRUPAL);
		}
		return $cms;
	}
}
