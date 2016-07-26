<?php
/**
 * Settings form for intra search api.
 */
function intra_search_api_admin_settings() {
	$form = array();

	$form['helpers'] = array(
		'#title' => t('Helpers'),
		'#type' => 'fieldset'
	);

	$wn = variable_get('wordnet_executable', '/usr/local/WordNet-3.0/bin/wn');
	$wn = (is_executable($wn)) ? $wn : '';

	$form['helpers']['wordnet_executable'] = array(
		'#title' => t('Wordnet Executable'),
		'#description' => t('Path to WordNet executable. If enabled, can be used to detect word synonyms'),
		'#type' => 'textfield',
		'#default_value' => $wn
	);

	$form['intra_search_magic'] = array(
		'#title' => t('Magicality by default'),
		'#description' => t('How much magic should be used by default when searching'),
		'#type' => 'select',
		'#options' => array(
			INTRA_SEARCH_MAGIC_NONE => '',
			INTRA_SEARCH_MAGIC => t('Less magic'),
			INTRA_SEARCH_MOREMAGIC => t('More magic')
		)
	);

	return system_settings_form($form);
}
