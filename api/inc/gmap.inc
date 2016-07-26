<?php

function intra_api_gmap_default() {

	// Some random position which shows scandinavia
	$map = array(
		'latlong' => '63.982544875,19.7218321875',
		'zoom' => 10,
		'id' => INTRA_API_NAME
	) + gmap_defaults();

	// Get owner company, and lon/lat for it
	$owner = Company::load(array('c_type' => 0))->current();
	if($owner) {
		if($pos = $owner->get('c_location')) {
			$map = array(
				'latlong' => sprintf('%f,%f', $pos['lat'], $pos['lon'])
			) + $map;
		}
	}

	return $map;
}

function _intra_company_gmap_relations(Company $company, array $map, &$drawn=array(), $nesting=0) {
	$mid = $company->get('c_id');

	$loc = $company->get('c_location');
	if(!$loc) return $map;

	if(!isset($map['markers'][$mid])) {
		$marker = array(
			'latitude' => $loc->lat,
			'longitude' => $loc->lon,
			'markername' => 'blank',
			'link' => url(intra_api_url($company).'/map'),
			'opts' => array(
				'title' => (string) intra_api_view($company)->asText()
			)
		);
		$map['markers'][$mid] = $marker;
	} else {
		return $map;
	}

	// Draw parents first, then own subsidiarys.
	if($parent = $company->get('c_parent_c_id')) {
		$map = _intra_company_gmap_relations(Company::load($parent), $map, $drawn, $nesting-1);
	}

	// Subsidarys
	$subs = Company::load(array('c_parent_c_id' => $company->get('c_id')));

	$style = $map['styles']['line_default'];

	if($nesting > 0) {
		// Subsidiary
		$style[2] = ($style[2]/($nesting+1));
	} elseif($nesting < 0) {
		// parent company
		$style[2] = 100+($style[2]/($nesting-1));
	}

	foreach($subs as $subsidiary) {
		$point = $subsidiary->get('c_location');
		if(!$point) continue;

		$sid = $sid = $company->get('c_id').':'.$subsidiary->get('c_id');
		if($drawn[$sid]) continue;
		$drawn[$sid] = true;


		$map['shapes'][] = array(
			'points' => array(
				array($loc->lat, $loc->lon),
				array($point->lat, $point->lon)
			),
			'type' => 'line',
			'style' => $style
		);

		$map = _intra_company_gmap_relations($subsidiary, $map, $drawn, $nesting+1);

	}
	return $map;
}

/**
 * Add customers into map markers
 */
function _intra_company_gmap_customers(Company $company, array $map) {

	foreach($company->customers() as $_comp) {
		if($map['markers'][$_comp->get('c_id')]) continue;

		$loc = $_comp->get('c_location');
		$marker = array(
			'latitude' => $loc->lat,
			'longitude' => $loc->lon,
			'link' => url(intra_api_url($_comp)),
			'markername' => 'green',
			'opts' => array(
				'title' => (string) intra_api_view($_comp)->asText()
			)
		);

		$map['markers'][$_comp->get('c_id')] = $marker;
	}
	return $map;
}

function intra_company_gmap_relations(Company $company, $map) {

	$markers = (isset($map['markers'])) ? $map['markers'] : array();
	$shapes  = (isset($map['shapes'])) ? $map['shapes'] : array();

	$map['markers'] = array();
	$map['shapes'] = array();

	$map = _intra_company_gmap_relations($company, $map);
	$map = _intra_company_gmap_customers($company, $map);

	$marker =& $map['markers'][$company->get('c_id')];
	if($marker) {
		$marker['autoclick'] = true;
		$marker['markername'] = '';
		$marker['text'] = theme('intra_company_infobox', $company, array());
		$marker['link'] = '';
	}

	$map['markers'] = array_merge($markers, $map['markers']);
	$map['shapes'] = array_merge($shapes, $map['shapes']);

	return $map;

}
