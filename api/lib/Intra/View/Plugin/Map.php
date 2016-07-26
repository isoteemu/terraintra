<?php
/**
 * Openlayers map with marker
 */
class Intra_View_Plugin_Map extends Intra_View_Plugin {
	/**
	 * Openlayers map array
	 */
	public $map = array();

	public function init() {
		if(!module_exists('openlayers'))
			throw new RuntimeException('Missing required module openlayers');

		openlayers_initialize();

		$this->map = _intra_api_openlayers_map();
	}

	public function map($preset=null) {
		$map = clone $this;
		if($preset) {
			// Load different map
			throw new Exception('Not implemented yet');
		}

		return $map;
	}

	/**
	 * Add company maker into a map
	 * @return Intra_View_Plugin_Map
	 *   Returns self.
	 */
	public function addCompanyMarker(Company $company) {
		if(!($geo = $company->get('c_location'))) {
			$geo = intra_api_geocode($company);
		}
		if(!$geo) return;

		$feature =& $this->addPoint($geo);
		$logo = intra_api_view($company)->favicon();

		$feature['attributes']['name'] = $company->get('c_cname');
		$feature['attributes']['date'] = $company->get('c_regdate');
		$feature['style'] = array(
			'externalGraphic' => (string) $logo['src'],
			'graphicOpacity' => 1,
			'graphicWidth' => (int) $logo['width'],
			'graphicHeight' => (int) $logo['height'],
			'graphicXOffset' => -ceil((int)$logo['width']/2),
			'graphicYOffset' => -ceil((int)$logo['height']/2),
		);

		return $this;
	}

	/**
	 * Add pointer into map
	 * @param $point Intra_Object_Gis_Point
	 * @param $layer String
	 *    Layer name which to add.
	 * @return Array
	 *    Vector marker
	 */
	public function &addPoint(Intra_Object_Gis_Point $point, $layer = 'points') {
		if(!isset($this->map['layers'][$layer])) {
			$this->map['layers'][$layer] = array(
				'type' => 'Vector',
				'name' => t('Points of interest'),
				'features' => array()
			);
		}

		$feature = array(
			'lat' => $point['lat'],
			'lon' => $point['lon']
		);

		$this->map['layers'][$layer]['features'][] =& $feature;

		if(count($this->map['layers'][$layer]['features']) == 1)
			$this->centerMap($point);

		return $feature;
	}

	/**
	 * Center map into point
	 */
	public function centerMap(Intra_Object_Gis_Point $point) {
		if(!isset($this->map['center'])) $this->map['center'] = array();

		$this->map['center']['lat'] = $point['lat'];
		$this->map['center']['lon'] = $point['lon'];
		$this->map['center']['zoom'] = 11;

		return $this;
	}

	public function __toString() {
		$map = openlayers_render_map($this->map);
		return theme('openlayers_map', $map);
	}

}