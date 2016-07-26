<?php
/**
 * @include Intra/Object.php
 */

/**
 * Filtering rules abstract.
 */
abstract class Intra_Filter_Rule {

	/**
	 * Rules for filter.
	 */
	protected $rule = array();

	/**
	 * Constructor for rule object.
	 * @param $rule
	 *   Rules for filter
	 */
	public function __construct($rule) {
		$this->rule = $rule;
		$this->init();
	}

	/**
	 * Extendable function to run on __construct()
	 */
	protected function init() {}

	/**	
	 * Does object match filtering arguments or not.
	 * @param $object Intra_Object
	 *   Object to filter against
	 * @return Bool
	 *   True if filtering matches, false if mismatch.
	 */
	public function accept(Intra_Object $object) {
		throw new Exception('Not implemented');
	}

	/**
	 * Converts filter rule into appropriate approximation for SQL.
	 * If not possible to implement to fetch exact things, fetch more
	 * rather than less.
	 * @return String
	 *   SQL where clause
	 */
	abstract public function __toString();

	/**
	 * Quote object key to be used as database identifier
	 * @return String
	 */
	protected function _quoteIdentifier($key) {
		return Intra_Helper::db()->quoteIdentifier($key);
	}

	/**
	 * Quote value to be used as database query clause.
	 * @see http://pear.php.net/package/MDB2/docs/latest/MDB2/MDB2_Driver_Datatype_Common.html#methodquote
	 * @return String
	 *   Quoted string.
	 */
	protected function _quoteValue($value) {
		return Intra_Helper::db()->quote($value);
	}
}
