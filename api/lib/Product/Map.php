<?php

class Product_Map extends Intra_Object {
	protected $dbTable = 'Product';
	protected $dbPrefix = 'pr';

	/**
	 * @name Product groups
	 * Bitmasks for product groups.
	 * TerraIntra products can be classified into different groups.
	 * Group affects into how price is calculated.
	 * @{
	 */
	const bundable   = 0x0001;
	const additional = 0x0002;
	const viewer     = 0x0004;
	const separate   = 0x0008;
	// All products
	const all        = 0x000f;
	/**
	 * @}
	 */

	/**
	 * Product families.
	 * Defines which products are viewer, can be
	 * bundled or are separate products.
	 */
	public static $groups = array(
		Product_Map::bundable => array(
			'01', ///< TerraModeler
			'02', ///< TerraScan
			'03', ///< TerraPhoto
			'19'  ///< TerraMatch
		),
		Product_Map::additional => array(
			'05', ///< TerraPipe
			'08', ///< TerraStreet
			'20', ///< TerraSurvey
			'81'  ///< TerraSlave
		),
		Product_Map::viewer => array(
			'22', ///< TerraScan viewer
			'23', ///< TerraPhoto viewer
		),
		Product_Map::separate => array()
	);

	protected $group = 0;

	public function &load($param) {

		if(is_array($param) && isset($param['group'])) {
			$group = $param['group'];
			unset($param['group']);
		}

		$r = parent::load($param, 'Product_Map');

		if(is_array($param)) {
			$r->each()->addGroup();
			$param = (isset($group)) ? array_merge($param, array('group' => $group)) : $param;
			return $r->each()->filter($param);
		} else {
			$r->addGroup();
			return $r;
		}

	}

	/**
	 * Add group info into product
	 */
	public function addGroup() {
		if($this->get('group') !== 0) {
			return $this;
		} elseif($this->get('pr_type') != 0) {
			return $this;
		}

		$pr_nr = $this->get('pr_nr');
		$mask = 0;
		foreach(array_keys(self::$groups) as $group) {
			if(in_array($pr_nr, self::$groups[$group])) {
				$mask |= $group;
			}
		}

		if($mask === 0)
			$mask = Product_Map::separate;

		$this->set('group', $mask);

		return $this;
	}

	public function filter($params=array()) {
		if(isset($params['group'])) {
			if(!($this->get('group') & $params['group']))
				return false;
			unset($params['group']);
		}
		return parent::filter($params);
	}
}
