<?php
/**
 * Settings form for intra company search.
 */
function intra_search_company_admin_settings() {
	$form = array();

	$limit = variable_get('company_suggest_limit', 7);
	$range = range(1, max(20, $limit+10));
	$form['company_suggest_limit'] = array(
		'#type' => 'select',
		'#title' => 'Autosuggestion result limit',
		'#options' => drupal_map_assoc($range),
		'#default_value' => $limit
	);

	$form['ranking'] = array(
		'#type'		=> 'fieldset',
		'#title'	=> t('Content ranking'),
		'#theme'	=> 'node_search_admin'
	);
	$form['ranking']['info'] = array(
		'#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately. To disable any factor, set its value to 0 and it will not be included in the search query.') . '</em>'
	);

	/// @see intra_search_company_company_ranking()
	$options = drupal_map_assoc(range(0, 10));
	foreach (module_invoke_all('company_ranking') as $var => $title) {
		$form['ranking']['factors'][$var] = array(
			'#title' => $title,
			'#type' => 'select',
			'#options' => $options,
			'#default_value' => variable_get($var, 5),
		);
	}
	return system_settings_form($form);
}
