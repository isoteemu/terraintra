<?php

class Intra_CMS_Common {

	public $callManager;

	const FORMAT_TEXT = 0;
	const FORMAT_HTML = 1;

	/**
	 * Overrides map.
	 * If overrides map contains function,
	 * it is called instead of stub function.
	 */
	public $overrides = array(
		'dfb' => array(__CLASS__, 'dfb'),
		'message' => array(__CLASS__, 'message'),
		'filter' => array(__CLASS__, 'filter')
	);

	public function init() {}

	public static function detect() {
		return false;
	}

	/**
	 * CMS Callbacks
	 * @{
	 */

	public function dfb() {
		return;
	}

	/**
	 * Filter format to safe HTML display. 
	 */
	public function filter($content, $input_format=null) {
		$content = strip_tags($content);
		$content = nl2br($content);
		return $content;
	}

	/**
	 * Send message to related stuff
	 * @param $object  Intra_Object
	 *   Object to which this event relates to
	 * @param $message 
	 *   Message to send. Should not be translated.
	 * @param $replacement
	 *   Translation replacemenets.
	 */
	public function message(Intra_Object $object, $message='') {

	}

	/**
	 * @} End of "CMS Callbacks"
	 */
}
