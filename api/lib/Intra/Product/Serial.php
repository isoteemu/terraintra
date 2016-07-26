<?php

class Intra_Product_Serial extends Intra_Product {

	/**
	 * @name Serial types
	 *   Serial sub-types.
	 * @see Intra_Product::$se_type
	 * @see Intra_Product::$productMap
	 * @{
	 */
	const workstation	= 0;
	const temporary		= 1;
	const pool			= 2;
	/**
	 * @}
	 */
	public $container = 'Intra_Product';

	protected $dbTable = array(
		'table' => 'Serial_nr',
		'Serial_pool' => 'Serial_pool'
	);

	/**
	 * Temporary store for allocated serial numbers.
	 * @deprecated Remove?
	 */
	protected static $allocatedSerials;

	public function __construct() {
		$this->set('se_lic_date', date('c'));
		if(!self::$allocatedSerials)
			self::$allocatedSerials = new Intra_Datastructure();

		$this->_accessors += array(
			'se_serial'		=> 'getSerial',
			'se_serial_old'	=> 'getOldSerial',
			'se_user_name'	=> 'getUserName'
		);

		parent::__construct();

	}

	/**
	 * @copydoc Intra_Object::__sleep()
	 */
	public function __sleep() {
		$attr = parent::__sleep();
		if(!$this->loadedFromDb) {
			unset($attr['se_serial']);
			unset($attr['se_old_serial']);
		}
		return $attr;
	}

	public function &load($param) {
		$r = parent::load($param, 'Intra_Product_Serial');
		return $r;
	}

	public function saveObject() {
		$this->set('se_chgdate', date('c'));
		return parent::saveObject();
	}

	/**
	 * Get serial.
	 * Return assigned serial, or if it's not set, allocates new.
	 */
	public function getSerial() {
		$serial = $this->_get('se_serial');

		if(!isset($serial) && !$this->loadedFromDb) {
			// If not in db, allocate serial
			$serial = $this->allocateSerial();
		}
		return $serial;
	}

	/**
	 * Accessor for \ref Intra_Product::$se_serial_old.
	 * If old serial is empty, sets it to current.
	 */
	public function getOldSerial() {
		$old_serial = $this->_get('se_old_serial');
		if(!isset($old_serial)) {
			$old_serial = $this->get('se_serial');
			$this->set('se_old_serial', $old_serial);
		}
		return $old_serial;
	}


	public function getUserName() {
		$username = $this->_get('se_user_name');
		if(!isset($username)) {
			$username = $this->generateUsername();
			$this->set('se_user_name', $username);
		}
		return $username;
	}

	/**
	 * Allocate new serial from Serial_pool table.
	 * @TODO this should work in a same way as new id allocation
	 */
	public function allocateSerial() {

		$pr_id = $this->get('pr_id');
		if($pr_id === null) {
			throw new Exception('No product ID defined for allocated product, which is required.');
			return false;
		}

		// Check serial type.
		$se_type = $this->get('se_type');

		if($se_type === null) {
			// Send which type of licenses?
			if(Company::load($this->get('c_id'))->isAcademic()) {
				$se_type = Intra_Product_Serial::temporary; // Temporary serial
			} else {
				$se_type = Intra_Product_Serial::workstation; // Workstation serial
			}
			$this->set('se_type', $se_type);
			$this->debug('No serial type defined, determined to use %s (%d)', ($se_type) ? 'temporary' : 'workstation', $se_type);
		} elseif(Company::load($this->get('c_id'))->isAcademic()) {
			$se_type = Intra_Product_Serial::temporary;
		} elseif($se_type == Intra_Product_Serial::pool) {
			// Pool licenses are identical to workstation in serial pool
			$se_type = Intra_Product_Serial::workstation;
		}


		if(($pr_nr = Product_Map::load($pr_id)->get('pr_nr')) == null) {
			throw new Exception('Could not find product type for product ID: '.$pr_id);
			return false;
		}

		$sql = $this->rewriteSql('SELECT `sp_id`, `pr_nr`, `sp_version`, `sp_type`, `sp_serial` FROM {Serial_pool} WHERE `pr_nr` = '.self::db()->quote($pr_nr).' AND `sp_type` = '.self::db()->quote($se_type).' ORDER BY `sp_version` DESC FOR UPDATE');

		$res = self::db()->query($sql);

		if($res->numRows() <= 0) {
			throw new Exception('No available serials found for '.Product_Map::load($pr_id)->get('pr_name').' (type:'.$se_type.')');
			return false;
		}

		$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);

		// Add new serial number into pool
		$sql2 = $this->rewriteSql('UPDATE {Serial_pool} SET sp_serial=sp_serial+1 WHERE sp_id=%d', $row['sp_id']);
		$res2 = self::db()->query($sql2);
		if(Pear::isError($res2)) {
			throw new RuntimeException('SQL Query error: '.$res->getMessage());
		}

		$serial = $row['pr_nr'].$row['sp_version'].$row['sp_serial'];

		$this->set('se_serial', $serial);
		return $serial;
	}

	/**
	 * Generate new username.
	 * Uses regex magic to detect previous license namings, and tries
	 * to select most appropriate names. If fails, uses
	 * Intra_Product_Serial::generateSafeName() to convert company name.
	 */
	public function generateUsername() {

		$products = Intra_Product::load(array(
			'!se_id'  => $this->get('se_id'),
			'se_c_id' => $this->get('se_c_id'),
			'pr_id'   => $this->get('pr_id'),
		))->sortChildren('se_serial', SORT_ASC);

		$inc = 1;
		$democracy = array();

		if($products && $products->count()) {
			foreach($products->getChildren() as $serial) {
				$match = array();

				if(preg_match('/(.*)([0-9]+)$/U', $serial->se_user_name, $match)) {

					$inc = max($inc, $match[2]+1);
					@$democracy[$match[1]]++;
				}
			}
		}

		// Generate name
		if(count($democracy)) {
			// From most used one
			arsort($democracy);
			$name = key($democracy);
			$name = substr($key, 0, 32-strlen($inc)) . $inc;
		} else {
			$name = $this->generateSafeName();

			if(Company::load($this->get('se_c_id'))->isAcademic()) {
				$name = substr($name, 0, 17-strlen($inc));
				$name = $name.' - academic lic'.$inc;
			} else {
				$name = substr($name, 0, 26-strlen($inc));
				$name = $name.' - lic'.$inc;
			}
		}

		return $name;
	}

	/**
	 * Generate Intra_Product::$se_user_name from company name.
	 */
	protected function generateSafeName() {
		$output = Company::load($this->get('se_c_id'))->get('c_cname');

		$ignore_words = array(
			'a', 'an', 'as', 'before', 'but', 'by', 'for', 'from', 'is', 'in',
			'into', 'like', 'off', 'on', 'onto', 'per', 'since', 'than', 'the',
			'this', 'that', 'to', 'up', 'via', 'with',
		);

		$ignore_re = '\b'. preg_replace('/,/', '\b|\b', $ignore_words ) .'\b';
		if (function_exists('mb_eregi_replace')) {
			$output = mb_eregi_replace($ignore_re, '', $output);
		}
		else {
			$output = preg_replace("/$ignore_re/i", '', $output);
		}

		$pattern = '/[^a-zA-Z0-9öäÖÄ\s-]+/ ';
		$output = preg_replace($pattern, '', $output);

		return $output;
	}

	/**
	 * Parse serial and return known information of it.
	 */
	public function parseSerial($serial=null) {
		if($serial === null) $serial = $this->get('se_serial');
		return array(
			'pr_nr' => substr($serial,0,2),
			'version' => substr($serial,2,3),
			'serial' => substr($serial,5)
		);
	}
}
