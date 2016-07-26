<?php

abstract class Intra_View_Plugin {

	protected $_view;

	public function __construct(Intra_View &$view) {
		$this->setView($view);
		$this->init();
	}

	protected function init() {}

	public function &getView() {
		if(!isset($this->_view))
			throw new Exception('Missing Intra_View reference object');
		return $this->_view;
	}

	public function setView(Intra_View &$view) {
		$this->_view =& $view;
	}
	/**
	 * Redirect call to view object
	 */
	public function &__call($func, $args) {
		return call_user_func_array(array(&$this->_view, $func), $args);
	}
}
