<?php
/**
 * NOT matching search.
 * @include ../Rule.php
 */

class Intra_Filter_Rule_NotIn extends Intra_Filter_Rule_In {

	public function accept($object) {
		return (parent::accept($object)) ? false : true;
	}

	public function __toString() {
		return parent::__toString(self::not);
	}
}
