<?php
/**
 * @file
 *   Agreement object.
 */
/**
 * Maintenance agreement product type handler.
 */

class Intra_Product_Agreement extends Intra_Product {

	/**
	 * Product ID in Product table.
	 */
	const PR_ID			= 157392178;

	/**
	 * Maintenance agreement "serial" typecode.
	 * @see Intra_Product::$se_type
	 * @see Codes_Map::$cd_value (cd_name = $se_type)
	 */
	const SE_TYPE		= 5;

	public $container	= 'Intra_Product';

	public function __construct() {
		$this->_accessors += array(
			'se_serial' => 'getAgreementNr'
		);
		parent::__construct();
	}

	public function &load($param) {
		$r = parent::load($param, 'Intra_Product_Agreement');
		return $r;
	}

	/**
	 * Accessor for Intra_Product::$se_serial.
	 * Fetch Company Agreement::$ag_nr, if Intra_Product_Agreement::$se_serial is not set.
	 */
	public function getAgreementNr() {
		$ag = $this->_get('se_serial');
		if(!$ag) {
			$c_id = $this->get('se_c_id');
			$ag = Agreement::load(array('se_c_id' => $c_id))->current()->get('ag_nr');
		}
		return $ag;
	}
}
