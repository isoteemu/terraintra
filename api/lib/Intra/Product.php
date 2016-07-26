<?php
/**
 * @file
 *   Class for Intra_Product
 */

class Intra_Product extends Intra_Object {

	protected $dbTable = 'Serial_nr';
	protected $dbPrefix = 'se';

	protected $_accessors = array(
		'se_rownr' => 'getRowNr'
	);

	/**
	 * Default object container.
	 * @see Intra_Object::$container
	 */
	public $container = 'Intra_Product';

	/**
	 * Map product numbers (SE_TYPE) into classes.
	 * @todo Add wrapper functions.
	 * @see Intra_Product::factory()
	 */
	public static $productMap = array(
		-1									=> 'Stub',
		Intra_Product_Serial::workstation	=> 'Serial',	///< Workstation license.
		Intra_Product_Serial::temporary		=> 'Serial',	///< Temporary license.
		Intra_Product_Serial::pool			=> 'Serial',	///< Pool license.
//		3	=> 'Stub',	// Unvalid
//		4	=> 'Stub',	// Unknown
		Intra_Product_Agreement::SE_TYPE	=> 'Agreement', // Maintenance
	);

	/**
	 * @name Database Schema
	 * @{
	 */
	public $se_id;			///< Key.
	public $in_id;			///< Is part of to Invoice: \ref Invoice::$in_id.
	public $pr_id;			///< Product ID number \ref Product_Map::$pr_id.
	public $se_c_id;		///< End customer number \ref Company::$c_id.
	public $se_p_id;		///< End user id \ref Person::$p_id
	public $se_serial;		///< Serial number
	public $se_type;		///< Serial type \ref Code::$cd_value ($cd_code=se_type)
	public $se_user_name;	///< Freetext name for serial.
	public $se_computer;	///< Assigned computer name.
	public $se_reg_date;	///< Registration date.
	public $se_valid_date;	///< Serial valid until -date.
	public $se_lic_date;	///< License generation date.
	public $se_rownr;
	public $se_title;
	public $se_count;
	public $se_unit;
	public $se_fee;
	public $se_discount;
	public $se_rem;
	public $se_agreement;	///< Has maintenance Agreement. (\c 'X' = \c True, \c null = \c False).
	public $se_serial_old;	///< Original (purchased) serial number. \ref Intra_Product::$se_serial.
	public $se_ordernr;
	public $se_maint_date;
	public $se_server_name;	///< License server name.
	public $se_server_id;	///< License server id.
	public $se_chgby;
	public $se_chgdate;

	/**
	 * @}
	 */

	public function &load($param) {
		return parent::load($param, 'Intra_Product');
	}

	/**
	 * @copydoc Intra_Object::factory()
	 * 
	 * Checks serial type (Intra_Product::$se_type) when building serial, and uses classname from
	 * Intra_Product::$productMap for building different types of products.
	 *
	 * @param $self
	 *   Internal toggle switch.
	 */
	public function &factory($class, $attr=array(), $self=false) {

		if(array_key_exists('se_type', $attr)) {
			$type = $attr['se_type'];
		} else {
			$type = self::defaultFactoryType($class);
		}

		if(!isset(self::$productMap[$type])) {
			$type = self::defaultFactoryType($class);
		} elseif(!class_exists('Intra_Product_'.self::$productMap[$type])) {
			$type = self::defaultFactoryType($class);
			self::debug('No suitable class, falling back to default type %s', $type);
		} // else A-OK

		$class = 'Intra_Product_'.self::$productMap[$type];

		return parent::factory($class, $attr, $self);
	}

	/**
	 * Detect class key from Intra_Product::$productMap based on class name.
	 * @param $class
	 *   Classname to search for
	 * @return
	 *   Intra_Product::$productMap key for suitable class.
	 */
	private function defaultFactoryType($class) {
		foreach(array_unique(self::$productMap) as $suffix) {
			if($class == 'Intra_Product_'.$suffix) {
				self::debug('Using class suffix %s as default product', $suffix);
				return array_search($suffix, self::$productMap);
			}
		}
		return -1;
	}

	/**
	 * Get associated Invoice.
	 * @return Invoice
	 */
	public function invoice() {
		return Invoice::load($this->get('in_id'));
	}

	/**
	 * Get row number (order).
	 * If no se_rownr is defined, calculates one from based on random magic
	 */
	public function getRowNr() {
		$nr = $this->_get('se_rownr');

		if(empty($nr)) {

			// If lock is on, return nothing.
			static $lock = false;
			if(!$lock) {

				$lock = true;
				$serials = $this->invoice()->articles()->sortChildren('se_serial', SORT_ASC)->sortChildren('se_rownr', SORT_DESC, SORT_ASC);

				$nr = 1;
				$need_offset = true;
				$old = array();
				foreach($serials as $serial) {
					// Empty values are on top
					if($need_offset) {
						if($this == $serial) {
							$need_offset = false;
						} else {
							$nr++;
						}
					} else {
						$_old = $serial->get('se_rownr');
						$old[] = ($_old) ? $_old : 0;
					}
				}
				$nr = (count($old)) ? $nr + max($old) : $nr;

				// Find my position, and and current biggest value
				$lock = false;
			}
		}
		return $nr;
	}

}
