<?php
/**
 * @file
 *   Person Object.
 */

/**
 * Person object.
 * ORM for Person table.
 * @todo When sleeping, serialize emails
 * @ingroup TerraIntra_Object
 */
class Person extends Intra_Object {

	/**
	 * @name Get Modes
	 * @see Person::getMode()
	 * @{
	 */
	/**
	 * Flag for retrieving data only from current Person -object.
	 */
	const GETMODE_SINGLE = 0x01;

	/**
	 * Flag for recursing data retriecal to Person Company.
	 */
	const GETMODE_RECURSE = 0x02;

	/**
	 * @} 
	 */
	/**
	 * @name Person types.
	 * @ingroup Codes
	 * @see Codes::$cd_value
	 * @{
	 */
	const TYPE_ADMIN		= -1;
	const TYPE_INTRA		= 0;
	const TYPE_DISTRIBUTOR	= 1;
	/**
	 * @}
	 */

	protected $dbTable = 'Person';
	protected $dbPrefix = 'p';

	/**
	 * @name Database Schema
	 * @{
	 */
	public $p_id;			///< Primary key.
	public $c_id;			///< Person Company \ref Company::$c_id
	public $p_dname;		///< Department name.
	public $p_fname:		///< first name.
	public $p_lname;		///< Last name.
	public $p_title;
	public $p_street;
	public $p_box;
	public $p_zip;			///< Areacode
	public $p_city;
	public $p_country;
	/**
	 * Primary email address.
	 * @deprecated Kept for compatibility. Emails are now stored on Person_email -table.
	 * @see Person::$_mutators['p_email']
	 * @see Person::setEmail()
	 * @see Person::getEmails()
	 */
	public $p_email;
	public $p_skype;
	public $p_phone;
	public $p_telefax;
	/**
	 * Person classes.
	 * Comma-separated list, but Person_Class is used to provide array accessor.
	 * @see Person::$_accessors['p_class']
	 */
	public $p_class;
	public $p_rem;
	/**
	 * Intra username.
	 * Should be unique, or null. Is used for different purposes:
	 * \li for TerraINTRA login.
	 * \li customer ftp-login.
	 * \li distributor price calculator login.
	 */
	public $p_user;
	/**
	 * Login password.
	 * Is plaintext representation, for old intra compatibility.
	 * @todo Hash.
	 */
	public $p_psw;
	public $p_login;		///< Last login datetime.
	public $p_mail_invoice;
	public $visible;		///< @see Intra_Object::$visible;
	/**
	 * @}
	 */

	protected $_accessors = array(
		'p_street'	=> 'getStreet',
		'p_box'		=> 'getBox',
		'p_zip'		=> 'getZip',
		'p_city'	=> 'getCity',
		'p_country'	=> 'getCountry',
		'p_phone'	=> 'getPhone',
		'p_telefax'	=> 'getFax'
	);

	protected $_mutators = array(
		'p_email' => 'setEmail',
		'p_class' => 'setClass'
	);

	/**
	 * Email collection for person.
	 * Emailcollection is instance of Person_Emailcollection, which is used to store
	 * different email addresses for Person.
	 * @see Person->_getEmails()
	 */
	protected $emailcollection;

	/**
	 * Active data retrieval mode.
	 * By default, only person attributes are retrieved:
	 * $Person->getMode(Person::GETMODE_SINGLE);
	 * But some attributes can be shared, like company address,
	 * and recursing into those can be set with
	 * $Person->getMode(Person::GETMODE_RECURSE)
	 * @see Person->getMode()
	 */
	protected $_getMode = Person::GETMODE_SINGLE;

	public function __construct() {
		
		$this->skipAttributes = array_merge($this->skipAttributes, array(
			'p_user',
			'p_psw',
			'_getMode'
		));

	}

	public function &load($param) {
		return parent::load($param, 'Person');
	}

	/**
	 * Set get mode for some params.
	 * @see Person::$getMode
	 * @param $mode
	 *   New mode. Either:
	 *   \li Person::GETMODE_RECURSE to enable recusing into Company attributes.
	 *   \li Person::GETMODE_SINGLE to retrieve only from own attributes.
	 * @return
	 *   Old mode if new mode is given, active if new is missing.
	 */
	public function getMode($mode=null) {
		$oldMode = $this->_getMode;
		if($mode !== null)
			$this->_getMode = $mode;
		return $oldMode;
	}

	/**
	 * Retrieve value from Person object and possibly from Company.
	 * If $Person->_getMode is set to Person::GETMODE_RECURSE, recurse
	 * into Company object for attribute looking, if Person one is missing.
	 * @param $personKey String
	 *   Key name to look from Person object.
	 * @param $companyKey String
	 *   Company object matching $personKey -key name.
	 * @return String
	 * @see Person::getMode()
	 */
	protected function _getJointValue($personKey, $companyKey=null) {
		if($companyKey === null) $companyKey = $personKey;
		$r = $this->_get($personKey);
		if($this->_getMode & Person::GETMODE_RECURSE && empty($r)) {
			$r = $this->getCompany()->get($companyKey);
		}
		return $r;
	}

	/**
	 * Set Person class
	 * @param $class
	 *   Can be either a Person_Class instance or String.
	 * @param $flag Int
	 *   Flag of Person_Class::replace or Person_Class::append.
	 */
	public function setClass($class='', $flag = Person_Class::replace) {
		if(is_string($class) || !($class instanceOf Person_Class))
			$class = new Person_Class($class);

		if($flag == Person_Class::append) {
			$class = array_merge($class, $this->get('p_class'));
			$class = array_unique($class);
		}
		$this->_set('p_class', $class);
	}

	/**
	 * Change/set new primary email address.
	 * @see Person::$p_email
	 */
	public function setEmail($addr) {

		$collection = $this->_getEmails();
		if(empty($addr)) {
			// Unset old :/
			$old = $this->get('p_email');
			$collection->each()->filter(array('%pe_email' => $old))->each()->delete();
		} else {
			$email = $collection->each()->filter(array('%pe_email' => $addr))->current();

			if(!$email) {
				$email = Person_Email::factory('Person_Email', array(
					'p_id'			=> $this->get('p_id'),
					'pe_email'		=> $addr,
					'pe_primary'	=> 1
				));
				$collection[] = $email;
			} else {
				$old = $collection->each()->filter(array(
					'pe_primary' => 1,
					'!pe_id' => $email->get('pe_id')
				))->each()->set('pe_primary', 0);

				$email->set('pe_primary', 1);
			}
		}
		$this->_set('p_email', (string) $addr);
	}

	/**
	 * Return person company object.
	 * @see Person::$c_id
	 * @return Company
	 *   Instance of Company
	 */
	public function &getCompany() {
		return Company::load($this->get('c_id'));
	}

	/**
	 * Accessor for Person::$p_box.
	 * @return \ref Company::$c_box if \ref Person::$p_box is missing.
	 */
	public function getBox() {
		return $this->_getJointValue('p_box', 'c_box');
	}

	/**
	 * Accessor for Person::$p_street.
	 * @return \ref Company::$c_street if \ref Person::$p_street is missing.
	 */
	public function getStreet() {
		return $this->_getJointValue('p_street', 'c_street');
	}

	/**
	 * Accessor for Person::$p_zip.
	 * @return \ref Company::$c_zip if \ref Person::$p_zip is missing.
	 */
	public function getZip() {
		return $this->_getJointValue('p_zip', 'c_zip');
	}


	/**
	 * Accessors for Person::$p_city.
	 * @return \ref Company::$c_city if \ref Person::$p_city is missing.
	 */
	public function getCity() {
		return $this->_getJointValue('p_city', 'c_city');
	}

	/**
	 * Accessor for Person::$p_country.
	 * @return \ref Company::$c_country if \ref Person::$p_country is missing.
	 */
	public function getCountry() {
		return $this->_getJointValue('p_country', 'c_country');
	}

	/**
	 * Accessor for Person::$p_phone.
	 * @return \ref Company::$c_phone if \ref Person::$p_phone is missing.
	 */
	public function getPhone() {
		return $this->_getJointValue('p_phone', 'c_phone');
	}

	/**
	 * Accessor for Person::$p_telefax.
	 * @return \ref Company::$c_telefax if \ref Person::$p_telefax is missing.
	 */
	public function getFax() {
		return $this->_getJointValue('p_telefax', 'c_telefax');
	}

	/**
	 * Person email addresses.
	 * @return Person_Emailcollection
	 *   Collection of email addresses
	 */
	public function getEmails() {

		$collection = $this->_getEmails();

		$primary = $this->_get('p_email');
		if(!empty($primary)) {
			$email = $collection->each()->filter(array('%pe_email' => $primary))->current();
			if(!$email) {

				$email = Person_Email::factory('Person_Email', array(
					'p_id'	   => $this->get('p_id'),
					'pe_email' => $primary,
					'pe_primary' => 1
				));
				$collection[] = $email;
			} else {
				$email->set('pe_primary', 1);
			}
		}
		$collection->sortChildren('pe_primary', SORT_DESC, SORT_NUMERIC);

		return $collection;
	}

	protected function &_getEmails() {
		$collection = new Person_Emailcollection();
		$collection->setReference($this);

		Person_Email::load(array('p_id' => $this->get('p_id')))->mergeChildrenTo($collection);
		return $collection;
	}

	/**
	 * Save Person emailcollection.
	 */
	protected function saveRelated() {
		$this->getEmails()->each()->saveObject();
	}

	/**
	 * Remove Person emailcollection
	 */
	protected function deleteRelated() {
		$this->getEmails()->each()->deleteObject();
	}

}
