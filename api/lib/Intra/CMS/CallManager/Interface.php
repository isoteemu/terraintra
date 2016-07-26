<?php

interface Intra_CMS_CallManager_Interface {
	public function init();
	/**
	 * Create new instance
	 */
	public function factory();

	/**
	 * Do the magic
	 */
	public function call($args=null);
}
