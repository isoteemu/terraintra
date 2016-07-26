<?php
/**
 * Case sensitive search.
 * @ingroup Filtering
 */
/**
 * @fn public function Intra_Filter->whereIn($key, $value)
 *   Where $key attribute value matches $value.
 */

class Intra_Filter_Rule_In extends Intra_Filter_Rule {

	const is  = 1;
	const not = 0;

	public function accept(Intra_Helper $item) {

		$val = $item->get($this->rule['key']);
		if(!is_array($val) && !($val instanceOf ArrayAccess)) {
			$val = array($val);
		}

		// Loop as long as no match is found.
		foreach($val as $iter) {
			if(is_array($this->rule['value']) || (is_object($this->rule['value']) && $this->rule['value'] instanceOf ArrayAccess)) {
				if(in_array($iter, $this->rule['value'])) {
					return true;
				}
			} else {
				if(strcmp($this->rule['value'], $iter) === 0) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param $mode
	 *   If self::not, use as "NOT IN" clause.
	 */
	public function __toString($mode = self::is) {
		if($this->rule['key'] == 'loadedFromDb') return '';

		if(is_array($this->rule['value'])) {
			$items = array();
			foreach($this->rule['value'] as $entry) {
				$items[] = Intra_Object::db()->quote($entry);
			}
			$in = implode(',', $items);
		} else {
			if(empty($this->rule['value']))
				$in = "''";
			else
				$in = $this->_quoteValue($this->rule['value']);
		}


		return sprintf('%1s %2s (%3s)',
			$this->_quoteIdentifier($this->rule['key']),
			($mode == self::is) ? ' IN' : 'NOT IN',
			$in
		);
	}
}