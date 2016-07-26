<?php
/**
 * @file Interface for CMS classes.
 */

interface Intra_CMS_Interface {
	/**
	 * Init function. Run after __construct().
	 */
	public function init();
	/**
	 * Detect CMS version.
	 */
	public static function detect();

}
