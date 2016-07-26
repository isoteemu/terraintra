<?php
/**
 * Plugin approach.
 */

class Intra_CMS_CallManager {
	private $instances = array();

	public function call($func, $args) {
		$object = $this->createInstance($func);
		if($object)
			return call_user_func_array(array($object, 'call'), $args);
	}

	public function __call($func, $args) {
		return $this->call($func, $args);
	}

	/**
	 * Create/Clone new instance.
	 */
	public function &createInstance($instance, $force = false) {
		$instanceName = strtolower($instance);
		if(!isset($this->instances[$instanceName]) || $force) {
			$class = $this->camelize($instance);
			$class = sprintf('%s_%s', __CLASS__, $class);

			$this->instances[$instanceName] = new $class();
			if($this->instances[$instanceName] instanceof Intra_CMS_CallManager_Interface)
				$this->instances[$instanceName]->init();
		}

		if($this->instances[$instanceName]) {
			return $this->instances[$instanceName]->factory();
		}
		throw new RuntimeException('No such instance '.$instance);
	}

	public function camelize($word) {
		return str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9]+/',' ',$word)));
	}
}
