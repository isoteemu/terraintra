<?php
function intra_activity_admin_settings() {
	$form = array();

	$form['limits'] = array(
		'#type' => 'fieldset',
		'#title' => t('Limits')
	);

	$times = array(
		604800, // 1 week
		2419200, // 1 month
		7257600, // 3 months
		15768000, // 6 months
		31536000, // 1 year,
		63072000 // 2 years
	);

	$times = drupal_map_assoc($times);
	foreach( $times as $key => $time) {
		$times[$key] =  format_interval($time);
	}

	$form['limits']['intra_activity_limit_age'] = array(
		'#type' => 'select',
		'#title' => t('Maximum age limit'),
		'#desctiption' => t('Exclude activities which are older than'),
		'#options' => $times,
		'#default_value' => 15768000
	);

	$limit = variable_get('intra_activity_limit', 20);
	$options = range(0, ($limit+20), 5);
	$options = drupal_map_assoc($options);

	$form['limits']['intra_activity_limit'] = array(
		'#type' => 'select',
		'#title' => t('Last activities limit'),
		'#description' => t('How many last activities are shown by default'),
		'#options' => $options,
		'#default_value' => $limit,
	);

	$limit = variable_get('intra_activity_limit_tooltip', 8);
	$options = range(0, ($limit+10));
	$options = drupal_map_assoc($options);

	$form['limits']['intra_activity_limit_tooltip'] = array(
		'#type' => 'select',
		'#title' => t('Tooltip activity limit'),
		'#description' => t('How many last activities are shown on tooltip'),
		'#options' => $options,
		'#default_value' => $limit,
	);

	$form['providers'] = array(
		'#type' => 'fieldset',
		'#title' => t('Activity providers')
	);

	// Format selector
	$formats = filter_formats();
	$format_options = array();

	foreach ($formats as $format) {
		$format_options[$format->format] = $format->name;
	}


	foreach(module_invoke_all('intra_activity_info') as $event => $info) {
		$prefix = 'intra_activity_provider_'.$event;

		$status = variable_get("{$prefix}_enabled", true);

		$form['providers'][$event] = array(
			'#type' => 'fieldset',
			'#collapsible' => true,
			'#collapsed' => !$status,
			'#title' => t($info['label']),
			'#description' => t($info['description']),
		);

		$form['providers'][$event]["{$prefix}_enabled"] = array(
			'#type' => 'checkbox',
			'#title' => t('Enable activity provider'),
			'#default_value' => $status
		);

		$form['providers'][$event]["{$prefix}_format"] = array(
			'#type' => 'select',
			'#title' => t('Input format'),
			'#options' => $format_options,
			'#default_value' =>  variable_get("{$prefix}_format", FILTER_FORMAT_DEFAULT)
		);

		$settings = module_invoke('intra_activity_provider_'.$event, 'settings');
		if($settings)
			$form['providers'][$event] += $settings;

	}

	$form['save'] = array(
		'#default_value' => t('Save'),
		'#type' => 'submit'
	);

	return system_settings_form($form);

}
