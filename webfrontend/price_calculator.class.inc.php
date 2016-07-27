<?php
define('INTRA_PRICE_CALCULATOR_INI', variable_get('file_directory_path', '/srv/ftp/pub/example.com/').'/ftp/prices.ini');
/**
 * @file
 *   Price calculator classes.
 */

class Price_Calculator_Bundle extends Intra_Datastructure {
	public $pr_name = 'Bundle';
	public $se_rem = 'Bundle';
	public $se_fee = 0;
	public $rate = 0;
	public $se_discount = 0;
}

/**
 * Calculate product prices
 * @todo Handle currencies
 */
class Price_Calculator extends Intra_Helper {
	const noPriceUpdate = 0;
	const updatePrice = 1;

	/**
	 * Maintenance price percent from list price
	 */
	public $maintenanceMultiplier = 0.15;
	/**
	 * maximum number for maintenance price discount multiplier
	 */
	public $maxMaintenanceIncrement = 11;

	public $bundleDiscounts = array(
		4 => 0.7,
		3 => 0.8,
		2 => 0.9,
		1 => 1
	);

	public $licenseDiscounts =  array(
		5 => 0.5,
		4 => 0.6,
		3 => 0.7,
		2 => 0.8,
		1 => 1
	);

	public $viewerDiscounts = array(
		9999 => 0.3,
		100 => 0.4,
		50 => 0.5,
		20 => 0.6,
		10 => 0.7,
		5 => 0.8,
		1 => 1
	);

	public $oldLicenses = array();

	public $periodFrom;
	public $periodTo;

	protected $company;

	protected $bundles = array();
	protected $separate = array();

	protected $maintenance = 0;

	public function __construct(&$company=null) {
		$ini = variable_get('intra_price_calculator_ini', INTRA_PRICE_CALCULATOR_INI);
		if(file_exists($file)) {
			$rules = parse_ini_file($ini, true);
			foreach($rules as $key => $val) {
				if(property_exists($this, $key))
					$this->{$key} = $val;
				else
					Intra_CMS()->dfb($key, 'Undefined property for price calculator');
			}
		}

		if($company) $this->company($company);

		$this->prices = new Intra_Helper();
		$this->separate = new Intra_Helper();

		// Set maintenance dates
		$m = date('n');
		$y = date('Y');
		if($m == 12) {
			$m = 1;
			$y = $y+1;
		} else {
			$m = $m+1;
		}
		$this->periodFrom = sprintf('%s/%s', $m, $y);

		$m = 12;
		$y = $y+1;
		$this->periodTo = sprintf('%s/%s', $m, $y);
	}

	public function &company($company=null) {
		if($company != null) {
			if($company instanceof Intra_Helper)
				$this->company = $company->get('c_id');
			else
				$this->company = $company;
		}

		return Company::load($this->company);
	}

	public function addProduct(&$serial) {
		$pr_id = $serial->get('pr_id');
		$product = Product_Map::load($pr_id);
		if($product->filter(array('group' => Product_Map::bundable))) {
			// Add into appropriate bundle
			$newBundle = true;
			foreach(array_keys($this->bundles) as $id) {
				if(!$this->bundles[$id]->hasChildren($pr_id)) {
					$newBundle = false;
					$this->bundles[$id]->addChildren($serial, $pr_id);
					break;
				}
			}

			if($newBundle) {
				// Create new bundle
				$tmp = new Price_Calculator_Bundle();
				$tmp->addChildren(&$serial, $pr_id);
				$this->bundles[] = $tmp;
			}
		} else {
			$this->separate->addChildren($serial);
		}
	}

	/**
	 * @return Bundles
	 */
	public function bundles() {
		$this->resort();

		$r = new Intra_Helper();
		foreach($this->bundles as &$items) {
			$this->bundleItems($items);
			$r->addChildren($items);
		}

		return $r;
	}

	/**
	 * @return Additional licenses
	 */
	public function additional() {
		$this->resort();

		$r = new Intra_Helper();
		foreach($this->separate->getChildren() as $item) {
			$this->separateItem($item);
			$r->addChildren($item);
		}
		return $r;
	}

	/**
	 * @return subtotal price of license sale
    */
	public function subTotal() {
		$price = 0;
		foreach($this->bundles()->each()->get('se_fee')->getChildren() as $fee) {
			$price += $fee;
		}

		foreach($this->additional()->each()->get('se_fee')->getChildren() as $fee) {
			$price += $fee;
		}
		return $price;
	}

	public function maintenancePrice($monthPrice=null) {
		if($monthPrice !== null) {
			$this->maintenance = $monthPrice;
		}

		return $this->maintenance*$this->periodLength();
	}

	public function total() {
		return $this->subTotal()+$this->maintenancePrice();
	}

	/**
	 * Move single bundle items into separates
	 */
	protected function resort() {
		foreach(array_keys($this->bundles) as $id) {
			if($this->bundles[$id]->count() == 1) {
				// Move into separates
				$item = current($this->bundles[$id]->getChildren());
				$this->separate->addChildren($item);
				unset($this->bundles[$id]);
			}
		}
	}

	protected function bundleItems(&$items) {
		$maxDiscount = max(array_keys($this->bundleDiscounts));
		$multi = $this->bundleDiscounts[min($maxDiscount, $items->count())];

		$bundlePrice = $listPrice = 0;

		foreach($items->getChildren() as $child) {
			$itemPrice = 0;
			$map = Product_Map::load($child->get('pr_id'));
			$lPrice = $map->get('pr_price');
			$child->set('pr_name', $map->get('pr_name'));

			$se_discount = $child->get('se_discount');
			$itemPrice = $child->get('se_fee');

			if($itemPrice <= 0 && $se_discount != 100) {
				$price = $this->unitPrice($child);
				$itemPrice = $price['price'] * $multi;

				$this->maintenance += $price['maintenance'];
				$child->set('se_fee', $itemPrice);
				$child->set('se_discount', 100-$itemPrice/$lPrice*100);
			} else {
				$lPrice = $itemPrice / ((100 - $se_discount)/100);
			}
			$listPrice += $lPrice;

			$bundlePrice += $itemPrice;
		}

		$items->set('se_fee', $bundlePrice);

		$items->set('rate', $listPrice * $multi);
		$items->set('se_discount', 100-$bundlePrice/($listPrice*$multi)*100);

		return $items;
	}

	/**
	 * Calculate single product price
	 */
	protected function separateItem(&$item) {
		$map = Product_Map::load($item->get('pr_id'));

		$lPrice = $map->get('pr_price');
		if(($itemPrice = $item->get('se_fee')) <= 0) {
			$price = $this->unitPrice($item);

			$this->maintenance += $price['maintenance'];
			$item->set('se_fee', $price['price']);
			$item->set('rate', $lPrice);
			$item->set('se_discount', 100-$price['price']/$lPrice*100);
			$item->set('pr_name', $map->get('pr_name'));
		}

		return;
	}

	/**
	 * Calculate price for one product. Do not call multiple times
	 */
	protected function unitPrice($product) {

		$this->prepareOldStruct($product);

		$price = array();

		$pr_id = $product->get('pr_id');
		$productMap = Product_Map::load(array('pr_id' => $pr_id));

		// Set default price
		$unitPrice = $productMap->get('pr_price');
		$price['price'] = $unitPrice;

		if($productMap->filter(array('group' => product_map::viewer))) {
			// Viewers are special
			foreach($this->viewerDiscounts as $num => $multi) {
				if($num >= $this->oldLicenses[$pr_id])
					$_price = $unitPrice*$multi;
				else
					break;
			}
			$price['price'] = $_price;

		} else {
			$price['price'] = $unitPrice * $this->licenseDiscounts[min(
				$this->oldLicenses[$pr_id],  max(array_keys($this->licenseDiscounts))
			)];
		}

		if($product->filter(array('%se_agreement' => 'X'))) {
			$licCount = min($this->maxMaintenanceIncrement, $this->oldLicenses[$pr_id]);
	
			$maintenance = ($unitPrice * $this->maintenanceMultiplier) - ($unitPrice * $this->maintenanceMultiplier) * ($licCount-	1) * 0.05;
			$price['maintenance'] = $maintenance/12;
		} else {
			$price['maintenance'] = 0;
		}
		return $price;
	}

	public function prepareOldStruct($product) {
		$pr_id = $product->get('pr_id');
		if(!array_key_exists($pr_id, $this->oldLicenses)) {
			if(isset($this->company)) {
				$this->oldLicenses[$pr_id] = $this->company()->articles(array(
					'pr_id' => $pr_id,
					'loadedFromDb' => true
//					'%se_agreement' => 'X'
				))->count();
			} else {
				$this->oldLicenses[$pr_id] = 0;
			}
		}

		self::debug('Found %d old licenses for %s', $this->oldLicenses[$pr_id], $pr_id);

		$this->oldLicenses[$pr_id]++;
	}

	public function periodFrom($from=null) {
		if(!empty($from)) $this->periodFrom = $from;
		return $this->periodFrom;
	}

	public function periodTo($to=null) {
		if(!empty($to)) return $this->periodTo = $to;
		return $this->periodTo;
	}

	/**
	 * Return maintenance period length in months
	 */
	public function periodLength() {
		$from = explode('/', $this->periodFrom);
		$to   = explode('/', $this->periodTo);

		if($this->periodFrom == $this->periodTo)
			return 0;

		$m = 0;
		if($to[1] > $from[1]) { // 12 x year
			$m += 12 * ($to[1] - $from[1]);
		}

		if($to[0] >= $from[0]) {
			$m += $to[0] - ($from[0]-1);
		} else {
			$m += 12 - ($from[0] - 1) + $to[0];
		}

		return $m;
	}
}

/**
 * A fucking shit hack class piss in the ass.
 * Academic version uses smarter approach.
 */
class Price_Calculator_Maintenance extends Price_Calculator {
	public function &company($company=null) {
		$r = parent::company($company);
		if($company !== null) {
			$this->maintenance = Agreement::load(array('se_c_id' => $r->get('c_id')))->get('ag_fee')/12;
		}

		return $r;
	}

	/**
	 * Add maintenance agreement serial.
	 */
	public function addProduct(&$serial) {
		$this->separate->addChildren($serial);
	}

	/**
	 * No bundled items - not yet atleast.
	 */
	public function bundleItems() {
		return new Intra_Helper();
	}

	public function additional() {
		static $r;
		if($r) return $r;

		/** @{ HACK HACK */
		$this->oldLicenses = array();
		$this->maintenance = 0;
		/** @} */

		$r = new Intra_Helper();
		$serials = new Intra_Helper();

		$serials = $this->company()->articles(array(
			'loadedFromDb' => true,
			'%se_agreement' => 'X',
		))->sortChildren('se_serial', SORT_ASC, SORT_STRING);

		foreach($serials->getChildren() as $item) {
			$display = clone $item;
			$display->set('se_fee', 0);

			$this->separateItem($display);
			if($display->get('se_fee') == 0) continue;
			$r->addChildren($display);
		}

		return $r;
	}

	public function prepareOldStruct($product) {
		$pr_id = $product->get('pr_id');
		if(!array_key_exists($pr_id, $this->oldLicenses)) {
			$this->oldLicenses[$pr_id] = 0;
		}
		return parent::prepareOldStruct($product);
	}

	/**
	 * Calculate single product price
	 */
	protected function separateItem(&$item) {
		$map = Product_Map::load($item->get('pr_id'));
		$period = $this->periodLength();
		$lPrice = $map->get('pr_price') * $this->maintenanceMultiplier / 12 * $period;

		if(($itemPrice = $item->get('se_fee')) <= 0) {
			$price = $this->unitPrice($item);

			$maint = $price['maintenance'] * $period;

			$item->set('se_fee', $maint);
			$item->set('rate', $lPrice);
			$item->set('se_discount', 100-($maint/$lPrice)*100);
			$item->set('pr_name', $map->get('pr_name'));
		}

		return $r;
	}


	public function subTotal() {
		$fees = $this->additional()->each()->get('se_fee')->getChildren();

		$fee = array_sum($fees);

		return $fee;
	}

	public function maintenancePrice($monthPrice=null) {
		// Oooh the iron-y
		return 0;
	}


	public function total() {
		
		$ag = Agreement::load(array('se_c_id' => $this->company()->get('c_id')));
		$fee = $ag->get('ag_fee');
		if($fee > 0) {
			return $fee / 12 * $this->periodLength();
		} else {
			$fee = $this->subTotal();

			drupal_set_message(t('Company maintenance price is %money in intra. It should be fixed to be %fee', array(
				'%money' => theme('money', 0, $ag->get('ag_currency')),
				'%fee' => theme('money', $fee/$this->periodLength()*12, 'EUR')
			)), 'warning');
			return $fee;
		}
	}

}

class Price_Calculator_Academic extends Price_Calculator {
	public $academicMaintenance = 0.15;

	public function addProduct(&$serial) {
		$pr_id = $serial->get('pr_id');

		if(!isset($this->bundles[$pr_id])) {
			$this->bundles[$pr_id] = new Price_Calculator_Bundle();
		}
		$this->bundles[$pr_id]->addChildren($serial);
	}


	/**
	 * Academic pays only for maintenance, so unit price is 0
	 */
	public function unitPrice($product) {
		$unitPrice = Product_Map::load(array('pr_id' => $product->get('pr_id')))->get('pr_price');

		return array(
			'price' => 0,
			'maintenance' => $unitPrice * $this->academicMaintenance / 12
		);
	}

	public function resort() {
		return;
	}

	public function additional() {
		return new Intra_Helper();
	}

	protected function bundleItems(&$items) {
		$pr_id = $items->current()->get('pr_id');

		$map = Product_Map::load($pr_id);
		$pr_name = $map->get('pr_name');

		$price = $this->unitPrice($items->current());
		if(!$items->get('_pc_only_once')) {
			$items->set('_pc_only_once', true);

			$this->maintenance += $price['maintenance'];
			$items->set('pr_name', $pr_name);
			$items->set('se_rem', $pr_name);
			$items->set('se_fee', $price['price']);
			$items->set('se_discount', 0);
			$items->set('rate', $price['price']);
		}

		$count = $items->count();

		$items->each()->set('pr_name', $pr_name);
		$items->each()->set('se_fee', $price['maintenance'] / $count);
		$items->each()->set('se_discount', 0);
		return $items;
	}
}

class Price_Calculator_Academic_Maintenance extends Price_Calculator_Academic {

	/**
	 * Add maintenance agreement serial.
	 */
	public function addProduct(&$serial) {
		if($serial instanceof Intra_Product_Agreement) {

			$serials = Company::load($serial->get('se_c_id'))->articles(array(
				'loadedFromDb' => true,
				'%se_agreement' => 'X',
				'!se_type' => Intra_Product_Agreement::SE_TYPE
			))->sortChildren('se_serial', SORT_ASC, SORT_STRING);

			foreach($serials->getChildren() as $item) {
				$display = clone $item;
				parent::addProduct($display);
			}
		} else {
			return parent::addProduct($serial);
		}
	}

}

class Price_Calculator_Rent extends Price_Calculator {
	/**
	 * Rates for rent prices.
	 * Key is period length, value is multiplier per month
	 */
	public $leasingRates = array(
		1 => 0.045,
		2 => 0.04,
		3 => 0.035,
		4 => 0.03,
		5 => 0.03,
		6 => 0.025,
	);

	protected function unitPrice($product) {
		$r = parent::unitPrice($product);

		$periodLength = $this->periodLength();
		$_pos = max(array_keys($this->leasingRates));
		$multiply = $this->leasingRates[min($_pos, $periodLength)];

		$r['price'] = $r['price'] * $multiply * $periodLength;
		return $r;
	}

	protected function bundleItems(&$items) {
		$r = parent::bundleItems($items);
		$this->recalculateTimedFee($r);
		return $r;
	}

	protected function separateItem(&$item) {
		$r = parent::separateItem($item);

		// Well, wtf?
		if(!$item->get('_pc_only_once')) {
			$this->recalculateTimedFee($item);
			$item->set('_pc_only_once', true);
		}

		return $r;
	}

	protected function recalculateTimedFee(&$item) {
		Intra_CMS()->dfb($item, 'Item Original');
		$periodLength = $this->periodLength();
		$_pos = max(array_keys($this->leasingRates));
		$multiply = $this->leasingRates[min($_pos, $periodLength)];

		$fee = $item->get('se_fee');
		$rate = $item->get('rate')  * $multiply * $periodLength;

		$item->set('se_discount', 100-100*$fee/$rate);
		$item->set('rate', $rate);

		Intra_CMS()->dfb($item, 'Item Reworked');
	}
}

