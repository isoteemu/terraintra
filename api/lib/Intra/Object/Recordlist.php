<?php
/**
 * Implements comma separated field as php-object.
 * Intra stores some variables as comma-separated lists, instead of one-to-many realations.
 * This class provides accessor/mutator for those, which can be then handled as directly
 * either as string or array.
 * 
 * Example:
 * @code
 * // Load string as ArrayObject
 * $list = new Intra_Object_Recordlist('User, Contact, Accountist');
 * // Outputs "3"
 * echo count($list);
 * // Outputs "Contact"
 * echo $list[1];
 *
 * $list[] = 'Manager;
 * // Outputs "User, Contact, Accountist, Manager"
 * echo $list;
 * @endcode
 */
class Intra_Object_Recordlist extends ArrayObject {

	/**
	 * Separator.
     * Used to split/join string into/from array
	 */
	const list_separator = ', ';

	/**
	 * Construct Recordlist from array or string
	 */
	public function __construct($array=array(), $flags=0, $iterator_class='ArrayIterator') {

		if(is_string($array)) {
			$array = explode(self::list_separator, $array);
		}
		if(!$array) $array = array();

		return parent::__construct($array, $flags, $iterator_class);
	}

	/**
	 * Convert object into string
	 * @return String
	 *   Returns ArrayObject values, which are joined with self::list_separator
	 */
	public function __toString() {
		return implode(self::list_separator, (array) $this->getIterator());
	}
}