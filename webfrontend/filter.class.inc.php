<?php

/**
 * Object filtering
 * @todo Implement and/or modes
 * @ingroup Filtering
 */
class Intra_Filter extends Intra_Helper {
	/**
	 * Class prefix for rule instances
	 */
	const rule_class_prefix = 'Intra_Filter_Rule';

	protected $whereRules = array();

	/**
	 * Callback function.
	 * If call begins with "where", creates appropriate rule class
	 * (if possible) and adds it into rule stack
	 * @param $args
	 *   If using "where" prefix, args should contain first value as a key,
	 *   and second value as a 
	 * @see Intra_Filter_Rule
	 */
	public function __call($func, $args) {
		if(stripos($func, 'where') === 0) {
			$c = substr($func, 5);
			$c = sprintf('%s_%s', self::rule_class_prefix, ucwords($c));

			if(class_exists($c))
				throw new BadMethodCallException('No such filter rule class '.$c);

			$this->whereRules[] = new $c(array(
				'key'	=> $args[0],
				'value'	=> $args[1],
			));

			return $this;

		} else {
			return call_user_func_array(array(parent, '__call'), array($func, $args));
		}
	}

	/**
	 * Run filterset against one object.
	 * @param $object
	 *   Intra_Object instance to run filter against
	 * @return Boolean
	 *   True if filter set match, false if not.
	 */
	public function filterMatch(Intra_Object $object) {
		foreach($this->whereRules as &$filter) {
			return $filter->accept($objects);
		}
		return $object;
	}

	public function filterItems(Intra_Helper $items) {
		$r = new Intra_Helper();
		foreach($items as &$object) {
			if($this->filterMatch($object))
				$r->addChildren($object);
		}
		return $r;
	}

	/**
	 * Convert into SQL where clause.
	 * Join filtering rules to single SQL query
	 */
	public function __toString() {
		$sql = join(' AND ', $this->whereRules);
		self::debug('Filter SQL: %s', $sql);
		return $sql;
	}
}

/**
 * Filtering rules.
 */
abstract class Intra_Filter_Rule {

	/**
	 * Rules for filter
	 */
	protected $rule = array();

	/**
	 * Constructor for rule object
	 * @param $rule
	 *   Rules for filter
	 */
	public function __construct($rule) {
		$this->rule = $rule;
	}

	/**	
	 * Does object match filtering arguments or not
	 * @param $object Intra_Object
	 *   Object to filter against
	 * @return Boolean
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
}

class Intra_Filter_Rule_Like extends Intra_Filter_Rule {

	public function accept(Intra_Helper $item) {
		if(strcasecmp($item->get($this->rule['key']), $this->rule['value']) == 0)
			return true;

		return false;
	}

	public function __toString() {
		return sprintf('%s LIKE %s',
			Intra_Object::db()->quoteIdentifier($this->rule['key']), 
			Intra_Object::db()->quote($this->rule['value'])
		);
	}
}

/**
 * Case sensitive search
 */
class Intra_Filter_Rule_In extends Intra_Filter_Rule {

	public function accept(Intra_Helper $item) {
		if(in_array($this->rule['value'], $item->get($this->rule['key']))
			return true;

		return false;
	}

	/**
	 * @param $not Boolean
	 *   If true, use as "NOT IN" clause. Please note that in MySQL
	 *   "NOT IN" means "<> ALL()", whereas class intercepts it as
	 *   "<> ANY()", like "IN" is " = ANY()".
	 */
	public function __toString($not = false) {
		if(is_array($this->rule['value']) || (is_object($this->rule['value']) && $this->rule['value'] instanceOf ArrayAccess)) {
			$items = array();
			foreach($this->rule['value'] as $entry) {
				$items[] = Intra_Object::db()->quote($entry);
			}
			$in = implode(',', $items);

		} else {
			$in = Intra_Object::db()->quote($this->rule['value']);
		}
		return sprintf('%s %s ANY (%s)',
			(($not) ? '<>' : '='),
			Intra_Object::db()->quoteIdentifier($this->rule['key']), $in
		);
	}
}

class Intra_Filter_Rule_NotIn extends Intra_Filter_Rule_In {

	public function accept($object) {
		return (parent::accept($object)) ? false : true;
	}

	public function __toString() {
		return parent::__toString(true);
	}

}
