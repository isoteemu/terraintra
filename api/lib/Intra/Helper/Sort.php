<?php
/**
 * Helper function for performing sorting functions.
 * A faster approach might be in future to use lambda functions.
 */

class Intra_Helper_Sort {

	public $field = 'id';
	public $sort_order = SORT_ASC;
	public $sort_flags = SORT_REGULAR;

	public function __construct($field, $sort_order=null, $sort_flags=null) {
		$this->field = $field;
		$this->sort_order = (isset($sort_order)) ? $sort_order : $this->sort_order;
		$this->sort_flags = (isset($sort_flags)) ? $sort_flags : $this->sort_flags;
	}

	/**
	 * Compare two objects by using $this->field, and return order
	 * depending on $this->order.
	 */
	public function sort($a, $b) {

		$aVal = $a->get($this->field);
 		$bVal = $b->get($this->field);

		switch($this->sort_flags) {
			case SORT_NUMERIC :
				if((float)$aVal === (float)$bVal) return 0;
				break;
			case SORT_STRING :
				if((string)$aVal === (string)$bVal) return 0;
				break;
			default :
				if($aVal == $bVal) return 0;
		}

		$sort = array($aVal, $bVal);
		sort($sort, $this->sort_flags);

		if($sort[0] === $aVal)
			return ($this->sort_order == SORT_DESC) ? 1 : -1;
		else
			return ($this->sort_order == SORT_DESC) ? -1 : 1;

	}
}