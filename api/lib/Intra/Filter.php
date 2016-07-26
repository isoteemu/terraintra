<?php
/**
 * Object filtering.
 * Provides both - object level and sql - filters at same time.
 * @todo Implement and/or modes
 * @todo Implement subqueries
 * @defgroup Filtering
 * @{
 */

class Intra_Filter extends Intra_Helper {
	/**
	 * Class prefix for rule instances.
	 */
	const rule_class_prefix = 'Intra_Filter_Rule';

	/**
	 * Rule stack.
	 */
	protected $whereRules = array();

	/**
	 * Fields which are used in this ruleset
	 */
	public $fieldIdx = array();

	/**
	 * Internal rule ID.
	 */
	protected $index = 0;

	/**
	 * Limit number
	 */
	public $limit = 0;

	/**
	 * Ordering rules.
	 * Key represents field, and value sorting order.
	 * So no multiple sortings on same column.
	 * @see Intra_Helper::sort()
	 */
	public $orderBy = array();

	/// TODO: Implement thease
	const mode_and	= ' AND ';
	const mode_or	= ' OR ';

	/**
	 * Callback function.
	 * If call begins with "where", creates appropriate rule class
	 * (if possible) and adds it into rule stack
	 * @param $args
	 *   If using "where" prefix, args should contain first value as a key,
	 *   and second value as a value
	 * @see Intra_Filter_Rule
	 */
	public function &__call($func, $args) {
		// Parse what was called
		$match = array();
		if(preg_match('/^(andWhere|where|orWhere)(\w+)$/i', $func, $match)) {
			$call = strtolower($match[1]);

			switch($call) {
				case 'where' :		// AND
				case 'andwhere' :	// AND
					break;
				case 'orwhere' :	// OR
					break;
			}

			$this->addRule($match[2],  $args[0], $args[1]);

			return $this;
		} else {
			return call_user_func_array(array(parent, '__call'), array($func, $args));
		}
	}

	/**
	 * Return new Filter object.
	 *
	 * Uses object cloning, as (I presume) it is faster than creating
	 * new classes every time.
	 *
	 * @return Instance of Intra_Filter
	 */
	public function factory() {
		static $filter;

		if(!isset($filter)) {
			$filter = new Intra_Filter;
		}
		return clone $filter;
	}

	/**
	 * Set filtering limits
	 * @param $limit Int
	 *    Number of items to return on Intra_Filter->filterItems();
	 * @param $index Int
	 *    Offset for returned items. Not implemented thou.
	 */
	public function &limit($limit=0, $index=0) {
		$this->limit = (int) $limit;
		return $this;
	}

	public function &orderBy($key, $order=SORT_ASC) {
		$this->orderBy[$key] = $order;
		return $this;
	}

	/**
	 * Add new rule into stack.
	 * @param $type String
	 *   Type of filter rule.
	 * @param $key Mixed
	 * @param $value Mixed
	 * @return Intra_Filter_Rule
     *   Returns instance of Intra_Filter_Rule
	 */
	public function &addRule($type, $key, $value) {

		$filter = sprintf('%s_%s', self::rule_class_prefix, ucwords($type));

		if(!class_exists($filter))
			throw new BadMethodCallException('No such filter rule class '.$filter);

		$id = $this->index++;

		$this->whereRules[$id] = new $filter(array(
			'key'	=> $key,
			'value'	=> $value,
		));

		if($this->whereRules[$id] instanceof Intra_Filter_Rule) {
			$this->fieldIdx[$key][] = $id;
		}
		return $this->whereRules[$id];
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
			if(!$filter->accept($object)) return false;
		}
		return $object;
	}

	/**
	 * Filter items according Intra_Filter rules
	 */
	public function &filterItems($items) {
		$r = new Intra_Helper();

		foreach($items as $object) {
			if($this->filterMatch($object)) {
				$r->addChildren($object);
			}
		}
		$this->orderItems($items);

		return $r;
	}

	public function &orderItems($items) {
		foreach(array_reverse($this->orderBy, true) as $field => $flags) {
			$items->sortChildren($field, $flags);
		}
		return $items;
	}

	/**	
	 * Return SQL where string
	 * @return String
	 */
	public function sqlWhere() {
		$sql = '1=1';
		$and = array();
		foreach($this->whereRules as $rule) {
			$cond = (string) $rule;
			if($cond) $and[] = $cond;
		}

		$sql = implode(' AND ', $and);

		return $sql;
	}

	/**
	 * Return SQL Order by string
	 * @return String
	 */
	public function sqlOrder() {
		if(count($this->orderBy)) {
			foreach($this->orderBy as $field => $sort) {
				$dir = '';
				if($sort == SORT_ASC)
					$dir .= ' ASC';
				elseif($sort == SORT_DESC)
					$dir .= ' DESC';
				$orderBy[] = Intra_Helper::db()->quoteIdentifier($field).' '.$dir;
			}
		}
		return (count($orderBy)) ? "ORDER BY ".implode(', ', $orderBy) : '';
	}

	/**
	 * Return SQL limit string
	 * @return String
	 */
	public function sqlLimit() {
		return ($this->limit > 0) ? "LIMIT 0, {$this->limit}" : '';
	}

	/**
	 * Convert into SQL where clause.
	 * Join filtering rules to single SQL query
	 */
	public function __toString() {
		$sql   = array();
		$sql[] = (string) $this->sqlWhere();
		$sql[] = (string) $this->sqlOrder();
		$sql[] = (string) $this->sqlLimit();

		return implode(' ', $sql);
	}
}
/**
 * @} End of Filtering
 */