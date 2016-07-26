<?php

/**
 * @include ../Intra/Object/Recordlist.php
 */

class Company_Class extends Intra_Object_Recordlist {
	public function __construct($array=array()) {
		if(empty($array))
			$array = array();

		return parent::__construct($array);
	}
}
