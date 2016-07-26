<?php

class Person_Class extends Intra_Object_Recordlist {

	// Replace flag
	const replace = 0;
	// Append flag
	const append  = 1;

	/**
	 * Constructor to allow null values
	 * @param $array Array
	 * @return instanceof ArrayObject
	 */
	public function __construct($array=array()) {
		return parent::__construct($array);
	}
}
