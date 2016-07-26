<?php

class Company extends Intra_Object {

	const TYPE_ADMIN = 0;

	protected $dbTable = 'Company';
	protected $dbPrefix = 'c';

	protected $articles;

	protected $_mutators = array(
		'c_class'	 => 'setClass',
		'c_location' => 'setLocation',
	);

	protected $_accessors = array(
		'c_regdate'		=> 'getRegDate',
		'c_url'			=> 'getUrl',
		'prospect_of'	=> '_getProspectOf'
	);

	public $academicClasses = array('ULKO_AKATEEMINEN', 'OPPILAITOS');

	const replace = 0;
	const append  = 1;

	/**
	 * @name Database Schema
	 * @{
	 */

	/**
	 * Company ID.
	 */
	public $c_id;

	/**
	 * @}
	 */


	public function &load($param) {
		return parent::load($param, 'Company');
	}

	/**
	 * Delete company and related stuff.
	 * Triggers removal of appropriate invoices and people.
	 */
	public function deleteObject() {
		$this->people()->each()->deleteObject();
		$this->invoices()->each()->deleteObject();
		parent::deleteObject();
	}

	public function articles($param=array()) {
		if(!isset($this->articles)) {
			$this->articles =& Intra_Product::load(array('se_c_id' => $this->get('c_id')));
		}
		$param = array_merge(array('se_c_id' => $this->get('c_id')), $param);
		return Intra_Product::load($param);
		//return $this->articles->each()->filter($param);
	}

	public function setClass($class='', $flag=self::replace) {
		if(is_string($class) || !($class instanceOf Company_Class))
			$class = new Company_Class($class);

		if($flag == self::append) {
			$class = array_merge($class, $this->get('c_class'));
			$class = array_unique($class);
		}
		$this->_set('c_class', $class);
	}

	/**
	 * Set location value.
	 * If passed value is not instance of Intra_Object_Gis_Point,
	 * thinks it's WKB and converts into Intra_Object_Gis_Point.
	 */
	public function setLocation($location=null) {
		if($location == null) return;
		if(!($location instanceOf Intra_Object_Gis_Point)) {
			$location = Intra_Object_Gis::fromWKB($location);
		}
		$this->_set('c_location', $location);
	}

	public function getRegDate() {
		$regdate = $this->_get('c_regdate');
		return (isset($regdate)) ? $regdate : date('c');
	}

	/**
    * Fetch customer list, and return as dumb array.
	 *
	 * @TODO: Check cached items
	 *
	 * Building thousands of objects to get customer list
	 * takes ... a while. About 350ms - which about 300ms too much.
	 * So fetch directly from database.
	 *
	 * @param $info additional fields to get
    *
	 * @return array of customers, where c_id is key.
	 */
	public function customerList($info=array('c_cname')) {
		$fields = array(self::db()->quoteIdentifier('c_id'));
		foreach($info as $field) {
			$fields[] = self::db()->quoteIdentifier($field);
		}

		if($this->get('c_type') != 0) {
			$c_id = $this->get('c_id');

			// Stupid ass mysql. Faster to make multiple fetches,
			// and join in PHP than having one query, which joins subquerys
			$sql = '(SELECT se_c_id FROM {Serial_nr} INNER JOIN {Invoice} ON {Invoice}.in_id = {Serial_nr}.in_id WHERE {Invoice}.c_id=%d GROUP BY se_c_id) UNION (SELECT se_c_id FROM  {Agreement} WHERE ag_dealer_c_id=%d GROUP BY se_c_id)';

			$sql = $this->rewriteSql($sql, $c_id, $c_id);
			$res = self::db()->query($sql);

			$c_ids = array();
			while($id = $res->fetchRow($sql)) {
				$c_ids[] = $id[0];
			}

			$sql = 'SELECT '.implode(',', $fields).' FROM {table} WHERE c_id IN ('.implode(',', $c_ids).') AND `visible` = 1';
			$sql = $this->rewriteSql($sql);

		} else {
			$sql = 'SELECT '.implode(',', $fields).' FROM {table} WHERE `visible` = 1';
			$sql = $this->rewriteSql($sql);
		}

		$res = self::db()->query($sql);
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$dblist[$row['c_id']] = $row;
		}

		return $dblist;

	}

	public function customers() {
		if($this->get('c_type') == 0) {
			// Admin company, get all companys
			$clist = Company::load(array());

		} else {
			$c_id = $this->get('c_id');
			$clist = new Intra_Datastructure();

			// Merge invoiced customers (company is distributor)
			Invoice::load(array('c_id' => $c_id))->each()->customer()->each()->mergeChildrenTo($clist, 'c_id');

			// Customers where company is maintenace responsible
			Agreement::load(array('ag_dealer_c_id' => $c_id))->each()->customer()->mergeChildrenTo($clist,'c_id');

			foreach($this->people() as $person) {
				Company::load(array('prospect_of' => $person->get('p_id')))->mergeChildrenTo($clist);
			}

			// ... And self
			$clist->addChildren($this, $c_id);
		}

		return $clist;
	}

	/**
	 * Return primary contact person
	 * @return Person
	 */
	public function getContact() {
		$rule = new Intra_Filter();
		$rule->whereIn('c_id', $this->get('c_id'));
		$rule->whereRecordlist('p_class', 'Contact');
		return Person::load($rule)->current();
	}

	public function people($param=array()) {
		return Person::load(array_merge(array('c_id' => $this->get('c_id')), $param));
	}
	/**
	 * Return prospect person
	 */
	public function getProspectOf() {
		$person = null;
		if($prospect = $this->get('prospect_of'))
			$person = Person::load($prospect);
		return $person;
	}

	public function getProspectBy() {
		$person = null;
		if($prospect = $this->get('prospect_by'))
			$person = Person::load($prospect);
		return $person;
	}

	/**
	 * Try detecting prospect.
	 * If prospect not defined, try looking from agreement.
	 */
	protected function _getProspectOf() {
		$prospect = $this->_get('prospect_of');

		if(!$prospect) {
			$ag = Agreement::load(array('se_c_id' => $this->get('c_id')))->current();
			if($ag) {
				$c_id = $ag->get('ag_dealer_c_id');
				if($c_id) {
					$company = Company::load($c_id);
					$distributor = $company->people(array('p_type' => Person::TYPE_DISTRIBUTOR))->current();
					if($distributor)
						$prospect = $distributor->get('p_id');
					elseif($contact = Company::load($c_id)->getContact())
						$prospect = $contact->get('p_id');

					if($prospect)
						$this->_set('prospect_of', $prospect);
				}
			}
		}
		return $prospect;
	}

	public function invoices($param=array()) {
		return Invoice::load(array_merge(array('c_id' => $this->get('c_id')), $param));
	}

	public function getUrl() {
		$url = $this->_get('c_url');
		if($url)
			$url = (preg_match('/^(http|https):\/\//i', $url)) ? $url : 'http://'.$url;
		return $url;
	}

	public function isAcademic() {
		return ($this->get('c_type') == 8) ? true : false;
	}
}
