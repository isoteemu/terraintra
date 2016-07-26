<?php
/**
 * Common settings
 */
function intra_api_admin_settings() {
	global $db_url;

	$form = array();
	$form['database'] = array(
		'#type'			=> 'fieldset',
		'#title'		=> t('%name Database settings', array('%name' => INTRA_API_NAME))
	);

	$form['database']['terra_intra_db'] = array(
		'#type'			=> 'textfield',
		'#default_value'=> variable_get('terra_intra_db', $db_url),
		'#title'		=> t('Database Connection DNS'),
	);

	$form['intra_system_filter_format'] = filter_form(variable_get('intra_system_filter_format', 2), null, array(
		'intra_system_filter_format'
	));
	$form['intra_system_filter_format']['#title'] = t('@name internal filtering format', array('@name' => INTRA_API_NAME));

	$form['3rdparty'] = array(
		'#type'			=> 'fieldset',
		'#title'		=> t('3rd party module settings')
	);

	if(module_exists('geocode')) {
		$geocodeHandlers = array();
		foreach(geocode_handler_info() as $key => $info) {
			$geocodeHandlers[$key] = $info['title'];
		}

		$geocoder = _intra_api_geocode_handler();

		$form['3rdparty']['intra_geocode_handler'] = array(
			'#type'			=> 'select',
			'#title'		=> t('Geocode handler'),
			'#required'		=> true,
			'#description'	=> t('Geocoding transforms addresses into geospatial information, which is then used to place them on the map. Select here preferred geocoding handler.'),
			'#default_value'=> $geocoder['handler'],
			'#options'		=> $geocodeHandlers
		);
	}

	if(module_exists('openlayers')) {
		$map = _intra_api_openlayers_map();

		$form['3rdparty']['intra_openlayers_preset'] = array(
			'#type'			=> 'select',
			'#title'		=> t('Mapping preset'),
			'#required'		=> true,
			'#description'	=> t('Which layerset to use. To edit default mapping layers, see <a href="!openlayers-presets-url">OpenLayers presets</a>.', array(
				'!openlayers-presets-url' => url('admin/settings/openlayers/presets')
			)),
			'#default_value'=> variable_get('intra_openlayers_preset', $map['preset_name']),
			'#options'		=> openlayers_get_presets('simple')
		);
	} else {
		drupal_set_message(t('<a href="@geocode-url">Openlayers</a> module not found. Mapping not available.', array(
			'@geocode-url' => 'http://drupal.org/project/openlayers')
		));
	}

	return system_settings_form($form);
}

function intra_api_admin_codes() {
	$form = array();

	$form['codes'] = array(
		'#theme' => 'fielset_tabbed'
	);

	foreach(Codes::arrayMap('CODES') as $cd => $name) {
		$form['codes'][$cd] = array(
			'#type' => 'fieldset',
			'#title' => $name
		);
		foreach(Codes::arrayMap($cd) as $code => $code_name) {
			$form['codes'][$cd][$code] = array(
				'#title' => " $code",
				'#type' => 'textfield',
				'#default_value' => $code_name
			);
		}
	}



	$form['invoice'] = array(
		'#type' => 'fieldset',
		'#title' => t('Invoice codes')
	);

	$invoices = Codes::arrayMap('IN_TYPE');

	$sale_invoices = drupal_map_assoc(explode(', ', INTRA_CODES_INVOICE_SALES));

	dfb(variable_get('intra_codes_invoice_sales', $sale_invoices));

	$form['invoice']['intra_codes_invoice_sales'] = array(
		'#title' => t('Sale -type invoices'),
		'#type' => 'select',
		'#multiple' => true,
		'#required' => true,
		'#default_value'=> variable_get('intra_codes_invoice_sales', $sale_invoices),
		'#options' => $invoices
	);

	return system_settings_form($form);
}

function theme_fielset_tabbed(&$form) {

	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.tabs.css', 'module', 'screen');
		jquery_ui_add(array('ui.tabs'));
	}

	$o   = array();
	$o[] = '<div class="fieldsets-as-tabs">';
	$o[] = '<ul class="nav">';

	$menu = $render = array();

	foreach (element_children($form) as $key) {
		if($form[$key]['#type'] == 'fieldset') {
			$form[$key]['#attributes']['id'] = form_clean_id('filesets-as-tab-'.$key);
			$menu[] = '<li><a href="#'.$form[$key]['#attributes']['id'].'">'.$form[$key]['#title'].'</a></li>';
		}
		$render[] = drupal_render($form[$key]);
	}

	$o[] = implode("\n  ", $menu);
	$o[] = '</ul>';
	$o[] = implode("\n  ", $render);

	$o[] =<<<EOL
<script>
$(document).ready(function() {
	$(".fieldsets-as-tabs").tabs();
});
</script>
EOL;

	$o[] = '</div>'; // tabs

	return implode("\n", $o);
}
