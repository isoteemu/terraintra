<?php

function intra_agreement_page($company) {
	return "<strong>Hello ".intra_api_view($company)."</strong>";
}

function template_preprocess_intra_agreement_details_lite(&$variables) {
	$variables['serials'] = array();
	$variables['view'] = intra_api_view($variables['agreement']);

	drupal_add_css(drupal_get_path('module', 'intra_agreement').'/css/agreement.css');

	foreach($variables['agreement']->customer()->articles(array('%se_agreement' => 'x')) as $product) {
		$id = $product->get('pr_id');
		if(!$variables['serials'][$id]) {
			$variables['serials'][$id] = array(
				'name' => Product_Map::load($product->get('pr_id'))->get('pr_name'),
				'count' => 0
			);
		}

		$variables['serials'][$id]['count']++;
	}

}
