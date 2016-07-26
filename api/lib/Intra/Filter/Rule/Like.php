<?php
/**
 * Case insensitive search.
 */

class Intra_Filter_Rule_Like extends Intra_Filter_Rule {

	public function accept(Intra_Helper $item) {
		$val = preg_quote($this->rule['value'],'/');
		$val = preg_replace('/([^\\\\]{0,1}%)/', '.*', $val);

		if(preg_match("/^$val$/i", $item->get($this->rule['key'])))
			return true;

		return false;
	}

	public function __toString() {
		return sprintf('%s LIKE %s',
			$this->_quoteIdentifier($this->rule['key']), 
			$this->_quoteValue($this->rule['value'])
		);
	}
}
