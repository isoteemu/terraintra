<?php

class Intra_CMS_CallManager_FirePHP extends Intra_CMS_CallManager_Instance {
	protected $firephp;

	public function init() {
		if(!class_exists('FirePHP'))
			@include_once('FirePHPCore/FirePHP.class.php');
		if(class_exists('FirePHP'))
			$this->firephp = new FirePHP();
	}

	public function call($args=null) {
		if(!$this->firephp) return $this;
		if($args) {
			call_user_func_array(array(&$this->firephp, 'fb'), $args);
		}

		return $this->firephp;
	}

	/**
	 * Firebug functions
	 */

	public function setEnabled() {}
	public function log() {}
	public function info() {}
	public function warn() {}
	public function error() {}
}
