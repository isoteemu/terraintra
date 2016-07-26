<?php

// Draft invoice number
define('INTRA_INVOICE_DRAFT', '0xDEADBEEF');

/**
 * @page Invoice_Currencys Invoice Currecies
 * Currecies are now expected to be in \c ISO-4217 codes.
 * 
 * But due old Intra contained bunch of different markups for same currencies, some hackery must be done.
 * So now Invoice::getCurrency() contains definitions to translate old markup, \c Mk, \c $, \c US$ into
 * meaningfull markup.
 * 
 * @see http://wikipedia.org/wiki/ISO_4217 
 */
/**
 * Company Invoice -object
 * @ingroup TerraIntra_Object
 */
class Invoice extends Intra_Object {

	protected $dbTable = array(
		'table' => 'Invoice',
		'Invoice_pool' => 'Invoice_pool'
	);

	protected $_accessors = array(
		'in_currency' => 'getCurrency', ///< @ref Invoice_Currencys
		'in_rate' => 'getRate'
	);

	protected $dbPrefix = 'in';
	protected $priceCalculator;

	/**
	 * @name Invoice Types
	 * @ingroup Codes
	 * @{
	 */
	const TYPE_SALE			= 0;
	const TYPE_MAINTENANCE	= 1;
	const TYPE_RENT			= 3;
	const TYPE_REJECTED		= 8;
	/**
	 * @}
	 */
	/**
	 * @name Database Schema
	 * @ingroup DatabaseSchema
	 * @{
	 */

	/**
	 * Invoice ID. 
	 * If is INTRA_INVOICE_DRAFT, is allocated on Invoice_Pool -table at Invoice::save().
	 * Type is int(10) 
	 * @see Invoice::allocateInvoice()
	 */
	public $in_nr = INTRA_INVOICE_DRAFT;
	
	public $in_id;							///< Invoice key.
	public $c_id = null;					///< Invoice billing companys \ref Company::$c_id.
	public $p_id;
	public $in_type = Invoice::TYPE_SALE; 	///< Invoice type -code.
	public $in_order_date;					///< Invoice order date.
	public $in_due_date;					///< Invoice due date.
	public $in_payment_date;				///< Invoice payment date.
	public $in_fee = 0;						///< Fee is price of invoice, in \ref $in_currency. @see getCurrency()
	public $in_currency = 'EUR';			///< Invoice currency. See \ref Invoice_Currencys.
	public $in_reference;					///< Reference number.
	public $in_vat_pros = 0;				///< Vat percent.
	public $in_rem;							///< Invoice remarks.
	public $x_file;							///< attached file location in TerraIntra.
	public $in_cust_reference;				///< Customer reference number.
	public $in_rate = 1.0;					///< Multiplier compared to EUR.
	public $in_receipt_email;
	public $in_chgby;						///< Username reference for \ref Person::$p_user of invoice creator.
	public $in_chgdate;						///< Invoice modification time.
	/**
	 * @}
	 */

	public function &load($param) {
		$r = parent::load($param, 'Invoice');
		return $r;
	}

	/**
	 * Constructor for Invoice.
	 * Adds some variables into \ref Intra_Object::$skipAttributes.
	 */
	public function __construct() {
		$this->skipAttributes[] = 'priceCalculator';
		$this->skipAttributes[] = '_articles';
		return parent::__construct();
	}

	/**
	 * Serialize callback.
	 * Serialize own articles too.
	 *
	 * @see Intra_Object::__sleep()
	 */
	public function __sleep() {
		$this->_articles = $this->articles();

		$this->debug(Intra_Product::load(array('in_id' => $this->get('in_id'))));

		$this->debug($this->_articles, "Invoice serialized items");

		return parent::__sleep();
	}

	/**
	 * Serialize callback.
	 * Serialize own articles too.
	 *
	 * @see Intra_Object->__wakeup()
	 */
	public function __wakeup() {
		parent::__wakeup();
		$this->_articles->each()->set('in_id', $this->get('in_id'));
		unset($this->_articles);
	}

	/**
	 * @copydoc Intra_Object::saveObject()
	 * Allocates invoice number if \ref Invoice::$in_nr is INTRA_INVOICE_DRAFT
	 */
	public function saveObject() {
		$in_nr = $this->get('in_nr');
		if($in_nr === null || $in_nr == INTRA_INVOICE_DRAFT) {
			$in_nr = $this->allocateInvoice();
			self::debug('New invoice number %s', $in_nr);
			$this->set('in_nr', $in_nr);
		}

		parent::saveObject();
	}

	/**
	 * Saves Invoice articles
	 * @copydoc Intra_Object::saveRelated()
	 */
	protected function saveRelated() {
		$this->articles()->each()->saveObject();
	}

	/**
	 * Delete invoice rows.
	 */
	public function deleteObject() {
		$this->articles()->each()->deleteObject();
		parent::deleteObject();
	}

	/**
	 * Return invoice articles.
	 * @return Intra_Helper
	 */
	public function &articles() {
		return Intra_Product::load(array('in_id' => $this->get('in_id')));
	}

	/**
	 * Add product into invoice.
	 */
	public function addArticle(Intra_Product &$product) {
		$product->set('se_c_id', $this->customer()->get('c_id'));
		$product->set('in_id', $this->get('in_id'));
	}

	/**
	 * Allocate invoice number.
	 * @throws RuntimeException
	 *  No Invoice numbers found / Invoice_pool is empty.
	 * TODO: Prevent dublicates.
	 */
	public function allocateInvoice($allocate = false) {
		$sql = $this->rewriteSql('SELECT `ip_id`, `ip_nr` FROM {Invoice_pool} WHERE IP_STATUS=0 ORDER BY IP_NR LIMIT 0, 1 FOR UPDATE');
		$res = self::db()->query($sql);

		if($res->numRows() <= 0) {
			throw new RuntimeException('No available invoice numbers found.');
			return false;
		}

		list($ip_id, $in_nr) = $res->fetchRow();
		$this->set('in_nr', $in_nr);

		$sql = $this->rewriteSql('UPDATE {Invoice_pool} SET IP_STATUS=1 WHERE IP_ID='.self::db()->quote($ip_id));
		$res &= self::db()->query($sql);

		return $in_nr;
	}

	/**
	 * Normalize currency.
	 * @return ISO-4217 currency code.
	 * @see Invoice_Currencys
	 */
	public function getCurrency() {
		$currency = $this->_get('in_currency');
		switch($currency) {
			case '$' :
			case 'US$' :
			case 'GEO' :
				$currency = 'USD';
				break;
			case 'CA$' :
				$currency = 'CAD';
				break;
			case '&#8364;' :
			case '€' :
			case '' :
				$currency = 'EUR';
				break;
		}
		return $currency;
	}
	/**
	 * Return normalized \ref Invoice::$in_rate.
	 * @return Integer
	 * @see Invoice_Currencys
	 */
	public function getRate() {
		$rate = $this->_get('in_rate');
		if($rate === null) {
			$cur = $this->get('in_currency');
			switch($cur) {
				case 'Mk' :
					$rate = 5.94573;
					break;
				case 'GEO' :
					$rate = 1.5;
					break;
			}
		}
		return $rate;
	}

	/**
	 * Set/get company to bill.
	 */
	public function billing($company=null) {
		if($company !== null) {
			if(is_object($company)) {
				$this->set('c_id', $company->get('c_id'));
				self::debug('Billing company changed to %s', $company->get('c_cname'));
			} elseif(is_numeric($company)) {
				$this->set('c_id', $company);
				self::debug('Billing company changed to $c_id: %d', $company);
			} else {
				throw new Exception('Given company is not numeric presentation of $c_id, or company object. Cant change billing.');
			}
		}
		try {
			$company = Company::load($this->get('c_id'));
			return $company;
		} catch(Exception $e) {
			$this->debug($e);
			return new Intra_Helper();
		}
	}

	/**
	 * Change all products in invoice to delivery to $company.
	 */
	public function &customer($company=null) {

		$r = new Intra_Helper();
		if($company !== null) {
			// Change delivery to
			if(is_object($company)) {
				$c_id = $company->get('c_id');
			} elseif(is_numeric($company)) {
				$c_id = $company;
				$company = Company::load($c_id);
			} else {
				throw new Exception('Given company is not numeric presentation of $c_id, or company object. Cant change customer');
			}
			if(isset($c_id)) {
				$this->set('c_id', $c_id);
				$this->articles()->each()->set('se_c_id', $c_id);

				//print_r($this->articles()->each()->get('se_c_id'));
				self::debug('Customer company changed to $c_id %d', $c_id);
				$r->addChildren($company, $c_id);
			}
		} else {
			// Return current deliverys
			if($this->articles()->count()) {
				foreach($this->articles()->each()->get('se_c_id')->getChildren() as $c_id) {
					try {
						if(!$r->hasChildren($c_id)) {
							$c = Company::load($c_id);
							$r->addChildren($c, $c_id);
						}
					} catch(Exception $e) {
						$this->debug($e);
					}
				}
			} elseif($c_id = $this->get('c_id')) {
				$c = Company::load($c_id);
				$r->addChildren($c, $c_id);
			} else {
				self::debug('Invoice '.$this->get('in_id').' has no customer');
			}
		}
		return $r;
	}

	/**
	 * Generate new price calculator object.
	 * @return Price_Calculator
	 */
	public function &prices() {
		if(!isset($this->priceCalculator)) {
			$prefix = ($this->customer()->isAcademic()) ? 'Price_Calculator_Academic' : 'Price_Calculator';
			switch($this->get('in_type')) {
				case Invoice::TYPE_MAINTENANCE :
					$class = "{$prefix}_Maintenance";
					break;
				case Invoice::TYPE_RENT :
					$class = "{$prefix}_Rent";
					break;
				default :
					$class = "{$prefix}";
					break;
			}

			$this->priceCalculator = new $class();

			$this->priceCalculator->company($this->customer()->current());

			$bund =& $this->articles()->getChildren();
			foreach($bund as &$serial) {
				$this->priceCalculator->addProduct($serial);
			}
		}

		return $this->priceCalculator;
	}
}
