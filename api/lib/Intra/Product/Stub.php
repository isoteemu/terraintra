<?php
/**
 * @file
 * @class
 * Dummy class for Intra_Product, which is used if no other is defined. 
 * @see Intra_Product::$productMap
 */
class Intra_Product_Stub extends Intra_Product {

 	public $container = 'Intra_Product';

	public function &load($param) {
		return parent::load($param, 'Intra_Product_Stub');
	}
}
