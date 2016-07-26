<?php

/**
 * Posix regular expression filter rule.
 * This is case insensitive.
 * @ingroup Filter
 */
class Intra_Filter_Rule_Regexp extends Intra_Filter_Rule {

	public function accept(Intra_Helper $item) {
		$regex = $this->rule['value'];
		$value = $item->get($this->rule['key']);

		return (eregi($regex, $value)) ? true : false;
	}

	public function __toString() {
		$sql = sprintf('%s REGEXP %s',
			$this->_quoteIdentifier($this->rule['key']), 
			$this->_quoteValue($this->rule['value'])
		);
		return $sql;
	}
}
