<?php
/**
 * each() helper class for objects.
 *
 * This has been now pretty much replaced by Traversable iterator
 */
class Intra_Helper_Each {

	/**
	 * Container
	 */
	private $_obj = array();

	/**
	 * Constructor for simple each call.
	 */
	public function __construct(array $array = array()) {
		if(count($array)) {
			$this->_obj = &$array;
		}
	}

	private function __call($function, $args=array()) {
		$r = new Intra_Helper();
		foreach(array_keys($this->_obj) as $objId) {
			$x = call_user_func_array(array(&$this->_obj[$objId],$function), $args);
			if($x)
				$r->addChildren($x, $objId);
		}
		return $r;
	}
}
