<?php
/**
 * @file
 *   Intra_Datastructure for handling dataentrys.
 */

/**
 * Datastructure with mutating object properties.
 * Intra_Datastructure is layer for providing accessors / mutators for object.
 * Mutators are used to write normalized variables for object, and similarry
 * accessors are for reading object properties as normalized.
 *
 * To register new accessor, it needs to be added into Intra_Datastructure::_accessors:
 * @code
 *   class Example extends Intra_Datastructure {
 *     public $ex_string = 'Uryyb Jbeyq';
 *     protected $_accessors = array(
 *       'ex_string' => 'getString'
 *     );
 *     
 *     public function getString() {
 *       return str_rot13($this->_get('ex_string'));
 *     }
 *   }
 *   $example = new Example();
 *   // Outputs "Hello World"
 *   echo $example->get('ex_string');
 * @endcode
 * Same should be done for mutators, but reversed.
 * @ingroup TerraIntra_Object
 */
class Intra_Datastructure extends Intra_Helper {

	/**
	 * @name Accessors and Mutators
	 * Accessors and Mutators registry.
	 * Key is a attribute name for which mutator/accessor is registered,
	 * and value is object function name, for what should be used as
	 * callback to set/retrieve value
	 * @{
	 */
	/**
	 * Mutator registry.
	 * Registered function should use _set() to set value of object property.
	 * @see Intra_Datastructure::set()
	 */
	protected $_mutators  = array();

	/**
	 * @see Intra_Datastructure::get()
	 */
	protected $_accessors = array();
	/**
	 * @}
	 */

	/**
	 * Getter function.
	 * If accessors is registered, uses it,
	 * otherwise uses protected _get function
	 * @param $key
	 *   String of key to retrieve
	 * @return
	 *   Returns attribute value. If value is not found, returns null.
	 */
	public function get($key) {
		if(isset($this->_accessors[$key]))
			return call_user_func(array(&$this, $this->_accessors[$key]), $key);
		elseif(property_exists($this, $key))
			return $this->_get($key);
		else
			Intra_CMS()->dfb('Access to undefined property '.$key.' in '.$this->container, 'WARN');
		return null;
	}

	/**
	 * Setter function
	 * Sets the object value, using simple key = value pairs
	 * @param $key
	 *   Key to which attribute to set
	 * @param $val
	 *   Value to set.
	 */
	public function set($key, $val) {

		if(isset($this->_mutators[$key])) {
			$args = func_get_args();
			array_shift($args);
			call_user_func_array(array(&$this, $this->_mutators[$key]), $args);
		} else
			$this->_set($key, $val);
		return $this;
	}

	/**
	 * Conditional setter function.
	 * Works likes Intra_Datastructure::set(), but won't replace
	 * old values.
	 * @see Intra_Datastructure::set()
	 */
	public function add($key, $val) {
		if(!$this->get($key)) $this->set($key, $val);
	}

	/**
	 * Return object property
	 * @param $key String
     *   Property name to return
	 * @return 
	 *   Returns object property
	 */
	protected function _get($key) {
		return $this->{$key};
	}

	/**
	 * Set object property
	 * @param $key String
	 *   Property name
	 * @param $val
	 *   Property value
	 */
	protected function _set($key, $val) {
		$this->{$key} = $val;
	}	

	/**
	 * Filter items based $param rules
	 *
	 * @todo Implement better filtering API
	 *
	 * @param $filter Intra_Filter
	 *   Primary method is instance of Intra_Filter ruleset.
	 *
	 * @return
	 *   self if matches, false if not.
	 */
	public function filter($filter) {
		if(is_array($filter)) {
			$filter = $this->_createFilter($filter);
		}
		return ($filter->filterMatch($this)) ? $this : false;
	}

	/**
	 * Convert old style array into Intra_Filter ruleset
	 * @param $params
	 *   Filter set params as array.
	 *   If first character of key is:
	 *     ! - does NOT match
	 *     % - Ignore case
	 * @return Intra_Filter
	 *   Intra object filter ruleset
	 */
	protected function _createFilter($params) {
		$ruleset = Intra_Filter::factory();

		$magic = array('!' => 1, '%' => 1);
		foreach($params as $key => $val) {
			$filter = '';

			if(array_key_exists($key[0], $magic)) {
				$filter = $key[0];
				$key = substr($key,1);
			}

			switch($filter) {
				case '!' :
					$ruleset->whereNotIn($key, $val);
					break;
				case '%' :
					$ruleset->whereLike($key, $val);
					break;
				default :
					$ruleset->whereIn($key, $val);
					break;
			}
		}
		return $ruleset;
	}
}