<?php

/**
 * Company infobox
 */
function intra_company_page_infobox($company) {

	$sections = module_invoke_all('intra_company_infobox', $company);

	echo theme('intra_company_infobox', $company, $sections);
}

/**
 * Hook for HOOK_intra_company_infobox().
 */
function intra_company_intra_company_infobox($company) {
	$r = array();

	// Detect subsidiarys
	if($sub = Company::load(array('c_parent_c_id' => $company->get('c_id')))) {

		$links = array();
		foreach($sub as $division) {
			$links[] = theme('intra_company_details_lite', $division);
		}

		if(count($links)) {
			$r['subsidiarys'] = array(
				'#title' => t('Subsidiarys'),
				'#value' => theme('item_list', $links)
			);
		}

	}
	if($parent = $company->get('c_parent_c_id')) {
			$r['c_parent'] = array(
				'#title' => t('Subsidiary of'),
				'#value' =>  theme('intra_company_details_lite', Company::load($parent))
			);
	}

	return $r;

}

function intra_company_page(&$company) {
	$view = intra_api_view($company);

	$subsidiarys = array();
	if($sub = Company::load(array('c_parent_c_id' => $company->get('c_id')))) {
		foreach($sub as $division) {
			$subsidiarys[] = theme('intra_company_details_lite', $division);
		}
	}

	return theme('intra_company_details', $company, $subsidiarys);
}

function intra_company_page_add() {
	$page = '';
	if($_GET['id'] && isset($_SESSION['intra_company']['add'][$_GET['id']])) {

		$company = unserialize($_SESSION['intra_company']['add'][$_GET['id']]);
		$cname = $company->get('c_cname');

		if($cname) {
			$results = _intra_company_search($cname);
			if(count($results)) {
				$resultView = array_map('intra_api_view', $results);
				$page .= theme('fieldset', array(
					'#title' => t('Did you mean'),
					'#value' => '<ul><li>'.implode('</li><li>',$resultView).'</li></ul>'
				));
			}
		}

		$page .= intra_company_page_edit($company);
	} else {
		$page .= drupal_get_form('intra_company_form_add');
	}

	return $page;
}

function intra_company_page_edit(Company &$company) {

	_intra_company_page_breadcrumb($company, l(t('Edit'), intra_api_url($company).'/edit'));

	drupal_add_css(drupal_get_path('module', 'intra_company').'/css/company-edit.css');

	return drupal_get_form('intra_company_form', $company);
}


function intra_company_page_dnd(Company $company) {

	// Hack access
	$oldMode = intra_api_accessmode();
	intra_api_accessmode(INTRA_API_ACCESSMODE_NONE);

	$view = intra_api_view($company);
	$row = array();

	if($company->get('c_oldname'))
		$row[] = array(t('Old name').':', (string) $view->get('c_oldname'));
	if($company->get('c_class'))
		$row[] = array(t('Taxonomy').':', (string) $view->get('c_class'));

	$row[] = array(array('data' => '', 'colspan' => 2));

	if($contact = $view->getContact())
		$row[] = array(t('Contact').':', $contact);

	if($manager = $view->getManager()) {
		if(!$contact || $manager != $contact)
			$row[] = array(t('Manager').':', $manager);
	}

	$row[] = array(array('data' => '', 'colspan' => 2));

	if($company->get('c_url'))
		$row[] = array(t('Website').':', $view->get('c_url'));
	if($company->get('c_email'))
		$row[] = array(t('Email').':', $view->get('c_email')->__toString());
	if($company->get('c_phone'))
		$row[] = array(t('Phone').':', $view->get('c_phone'));
	if($company->get('c_telefax'))
		$row[] = array(t('Fax').':', $view->get('c_telefax'));

	$row[] = array(array('data' => '', 'colspan' => 2));

	if($company->get('c_street'))
		$row[] = array(t('Street').':', $view->get('c_street'));
	if($company->get('c_box'))
		$row[] = array(t('P.O. Box').':', $view->get('c_box'));
	if($company->get('c_zip'))
		$row[] = array(t('Zip').':', $view->get('c_zip'));
	if($company->get('c_city'))
		$row[] = array(t('City').':', $view->get('c_city'));
	if($company->get('c_country'))
		$row[] = array(t('Country').':', $view->get('c_country'));

	$caption = '<h3 style="font-size:110%; font-weight:bold;">'.$view.'</h3>';

	echo theme('table', null, $row, array(
		'class' => 'vcard company'
	), $caption);

	intra_api_accessmode($oldMode);
}

/**
 * Votes company as cool one.
 * This data then can be used to sort companys.
 */
function intra_company_page_ping(Company $company) {

/*
  if (!drupal_valid_token($_GET['token'], $company->get('c_id'))) {
    watchdog('Plus1', 'Voting form error: Invalid token.');
    return drupal_access_denied();
  }
*/

	$r = intra_company_vote($company);
	echo drupal_json($r);

}

/**
 * Display map and company as marker.
 */
function intra_company_page_map(Company $company) {
	$point = $company->get('c_location');
	$point = ($point) ? $point : intra_api_geocode($company, false);
	if(!$point) {
		drupal_set_message(t('Company location could not be detected. Check address.'), 'error');
		drupal_not_found();
		return;
	}

	if(module_exists('gmap')) {
		require_once(drupal_get_path('module', 'intra_api').'/inc/gmap.inc');
		$map = intra_api_gmap_default();
		$map = array_merge($map, array(
			'width' => '100%',
			'height' => '500px',
			'latitude' => $point->lat,
			'longitude' => $point->lon
		));

		$map = intra_company_gmap_relations($company, $map);

		return theme('gmap', array('#settings' => $map));

	} elseif(module_exists('openlayers')) {
		$map = array(
			'height' => '600px',
			'controls' => array(
				'PanZoomBar' => true,
				'Navigation' => true,
				'Permalink' => true,
				'ZoomToMaxExtends' => true,
				'OverviewMap' => true
			),
			'center' => array(
				'zoom' => 6
			)
		);
		$map = openlayers_merge_maps(_intra_api_openlayers_map(), $map);
		$map = openlayers_render_map($map);

		return theme('openlayers_map', $map);
	}
}

function _intra_company_page_breadcrumb(Company $company, $subtitle=null) {

	$breadcrumb = drupal_get_breadcrumb();
	if(module_exists('intra_search_company'))
		$breadcrumb[] = l(t('Company'), 'search/intra_search_company/'.drupal_urlencode($company->get('c_cname')));
	$breadcrumb[] = l($company->get('c_cname'), intra_api_url($company));
	if($subtitle) {
		$breadcrumb[] = $subtitle;
	}
	drupal_set_breadcrumb($breadcrumb);
}

function intra_company_form_add(&$form_state) {
	$form = array();
	$form['set'] = array(
		'#type' => 'fieldset',
		'#title' => t('Add new company')
	);

	$form['set']['company'] = array(
		'#type' => 'textfield',
		'#title' => t('Company magic'),
		'#description' =>implode("\n", module_invoke_all('help', 'intra/company/add#company', drupal_help_arg())),
		'#default_value' => $_GET['c_cname']
	);

	if($_GET['c_email']) {
		$form['c_email'] = array(
			'#type' => 'hidden',
			'#value' => $_GET['c_email']
		);
	} elseif($_GET['email']) {
		$form['c_email'] = array(
			'#type' => 'hidden',
			'#value' => $_GET['email']
		);
	}

	$form['c_phone'] = array(
		'#type' => 'hidden',
		'#value' => $_GET['c_phone']
	);

	$form['c_city'] = array(
		'#type' => 'hidden',
		'#value' => $_GET['c_city']
	);

	$form['c_country'] = array(
		'#type' => 'hidden',
		'#value' => $_GET['c_country']
	);

	$form['buttons'] = array();
	$form['buttons']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Create'),
		'#weight' => 5,
		'#attributes' => array(
			'class' => 'create'
		)
	);

	return $form;
}

function intra_company_form_add_submit($form, &$form_state) {

	$company = Company::factory('Company');

	if($form_state['values']['c_email'] && valid_email_address($form_state['values']['c_email'])) {
		$company->set('c_email', $form_state['values']['c_email']);
	}

	if($form_state['values']['c_class']) {
		$class = (array) new Person_Class($form_state['values']['c_class']);
		$codes = array_keys(Codes::arrayMap('C_CLASS'));
		$p_class = array_intersect($codes, $class);
		$company->set('c_class', $p_class);
	}

	$company->set('c_phone', $form_state['values']['c_phone']);
	$company->set('c_city', $form_state['values']['c_city']);
	$company->set('c_country', $form_state['values']['c_country']);

	module_invoke_all('intra_detect_company', $company, $form_state['values']['company']);

	$id = $form_state['values']['form_build_id'];

	$_SESSION['intra_company']['add'][$id] = serialize($company);

	$form_state['redirect'] = array(
		'intra/company/add',
		'id='.drupal_urlencode($id)
	);
}

function intra_company_form(&$form_state, $company) {

	$form = array();
	$form['details'] = array(
		'#type' => 'fieldset',
	);

	$form['details']['c_cname'] = array(
		'#title' => t('Name'),
		'#required' => true,
		'#type' => 'textfield',
		'#default_value' => $company->get('c_cname'),
		'#description' => t("If you're editing existing company, and change name, old name is saved and displayed on closures at company details page")
	);

	$classes = Codes::arrayMap('C_CLASS');
	$cClass = (array) $company->get('c_class');

	$form['details']['c_class'] = array(
		'#title' => t('Taxonomy'),
		'#type' => 'select',
		'#multiple' => true,
		'#options' => $classes,
		'#default_value' => $cClass,
	);

	$form['tabs'] = array(
		'#theme' => 'fielset_tabbed'
	);

	$form['tabs']['details'] = array(
		'#title' => t('Contact'),
		'#type' => 'fieldset'
	);

	$form['tabs']['details']['c_url'] = array(
		'#title' => t('Website'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_url'),
		'#description' => t('Company website url')
	);

	$form['tabs']['details']['c_email'] = array(
		'#title' => t('Email'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_email')
	);

	$form['tabs']['details']['c_phone'] = array(
		'#title' => t('Phone'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_phone')
	);

	$form['tabs']['details']['c_telefax'] = array(
		'#title' => t('Fax'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_telefax')
	);

	$form['tabs']['intra'] = array(
		'#title' => t('Customer'),
		'#type' => 'fieldset'
	);

	$types = Codes::arrayMap('C_TYPE');
	$type = $company->get('c_type');

	if(!isset($type)) $type = 2;

	$form['tabs']['intra']['c_type'] = array(
		'#title' => t('Type'),
		'#type' => 'select',
		'#options' => $types,
		'#default_value' => $type,
		'#description' => t('Company relationship type to self.')
	);


	$parent_id = $company->get('c_parent_c_id');
	if($parent_id) {
		try {
			
			$parent = Company::load($parent_id);
		} catch(Exception $e) {}
	}


	// Add company selection
	$form['tabs']['intra'] += intra_company_formelement('c_parent_c_id');
	$form['tabs']['intra']['c_parent_c_id']['#title'] = t('Subsidiary of');
	$form['tabs']['intra']['c_parent_c_id']['#default_value'] = ($parent) ? $parent->get('c_id') : '';
	$form['tabs']['intra']['c_parent_c_id']['#required'] = false;

	$prospects = array();

	$distributors = new Intra_Datacollection();
	Company::load(array('c_type' => 0))->mergeChildrenTo($distributors);
	Company::load(array('c_type' => 1))->mergeChildrenTo($distributors);

	foreach($distributors as $_company) {
		$people = array();
		foreach($_company->people() as $person) {
			$people[$person->get('p_id')] = sprintf('%s %s', $person->get('p_fname'), $person->get('p_lname'));
		}
		if(count($people)) $prospects[$_company->get('c_cname')] = $people;
	}

	$prospect_by = intra_company_detect_prospect($company, 'by');
	$prospect_of = intra_company_detect_prospect($company, 'of');

	if(!$prospect_by)
		$prospect_by = intra_api_currentuser()->get('p_id');

	if(!$prospect_of)
		$prospect_of = intra_api_currentuser()->get('p_id');

	$form['tabs']['intra']['prospect_by'] = array(
		'#title' => t('Prospect by'),
		'#desctiption' => t('Original distributor'),
		'#type' => 'select',
		'#required' => false,
		'#options' => $prospects,
		'#default_value' => $prospect_by,
		'#description' => t('Lead name')
	);

	$form['tabs']['intra']['prospect_of'] = array(
		'#title' => t('Customer of'),
		'#type' => 'select',
		'#required' => false,
		'#options' => $prospects,
		'#default_value' => $prospect_of,
		'#description' => t('Who is primary agent for this company')
	);

	$form['tabs']['address'] = array(
		'#title' => t('Address'),
		'#type' => 'fieldset'
	);

	$form['tabs']['address']['c_street'] = array(
		'#title' => t('Street'),
		'#type' => 'textarea',
		'#rows' => 2,
		'#default_value' => $company->get('c_street')
	);

	$form['tabs']['address']['c_box'] = array(
		'#title' => t('P.O. Box'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_box')
	);

	$form['tabs']['address']['c_zip'] = array(
		'#title' => t('Postcode'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_zip')
	);

	$form['tabs']['address']['c_city'] = array(
		'#title' => t('City'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_city')
	);

	$form['tabs']['address']['c_country'] = array(
		'#title' => t('Country'),
		'#type' => 'select',
		'#options' => _intra_company_countryselect(),
		'#default_value' => $company->get('c_country'),
		'#attributes' => array(
			'class' => 'countryselect'
		)
	);

	$form['tabs']['billing'] = array(
		'#title' => t('Billing'),
		'#type' => 'fieldset'
	);

	if($vatid = $company->get('c_vat')) {
 		$status = @intra_company_vatid($vatid);
		$_path = drupal_get_path('module', 'intra_api');
		if($status['status'] == 'success') {
			$vatCheck = theme('image', $_path.'/image/dialog-ok.png');
		} elseif($status['status'] == 'error') {
			$vatCheck = theme('image', $_path.'/image/dialog-fail.png');
		} else {
			$vatCheck = '<!-- Fail -->';
		}
	}

	/// TODO: add vatid check
	$form['tabs']['billing']['c_vat'] = array(
		'#title' => t('Vat ID'),
		'#type' => 'textfield',
		'#prefix' => '<div class="vat-id-check">',
		'#suffix' => '<span class="vat-id-check-status">'.$vatCheck.'</span></div>',
		'#default_value' => $vatid,
/*
		'#element_validate' => array(
			'intra_company_validate_vatid'
		),
*/
	);

	$form['tabs']['billing']['c_iban'] = array(
		'#title' => t('IBAN'),
		'#type' => 'textfield',
		'#disabled' => true,
		'#maxlength' => 14,
		'#default_value' => $company->get('c_iban')
	);
	if(module_exists('banking') && module_exists('content')) {
		$form['billing']['c_iban']['#element_validate'][] = 'banking_textfield_validate';
	}

	$form['tabs']['billing']['c_discount'] = array(
		'#title' => t('Discount %'),
		'#type' => 'textfield',
		'#default_value' => $company->get('c_discount'),
		'#required' => false,
		'#element_validate' => array(
			'intra_company_validate_discount'
		),
		'#description' => t('Discount percent for all sales')
	);

	$form['tabs']['rem'] = array(
		'#title' => t('Remarks'),
		'#type' => 'fieldset',
	);

	$form['tabs']['rem']['c_rem'] = array(
		'#title' => t('Remarks'),
		'#type' => 'textarea',
		'#rows' => 5,
		'#default_value' => $company->get('c_rem')
	);

	$form['buttons'] = array(
		'#prefix' => '<div class="ui-dialog-buttonpane ui-helper-clearfix">',
		'#suffix' => '</div>',
	);
	$form['buttons']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Save'),
		'#weight' => 5,
		'#attributes' => array(
			'class' => 'ui-corner-all'
		)
	);
	if($company->get('loadedFromDb')) {

		$visible = $company->get('visible');
		$form['buttons']['visibility'] = array(
			'#type' => 'submit',
			'#value' => ($visible) ? t('Hide') : t('Show'),
			'#weight' => 10,
			'#submit' => array('intra_company_form_visibility_toggle'),
			'#attributes' => array(
				'class' => 'ui-corner-left '.(($visible) ? 'hide' : 'show')
			)
		);

		$form['buttons']['delete'] = array(
			'#type' => 'submit',
			'#value' => t('Delete'),
			'#weight' => 15,
			'#submit' => array('intra_company_form_delete_submit'),
			'#attributes' => array(
				'class' => 'ui-corner-right',
				'onclick' => sprintf("return confirm('%s');", addslashes(t('You are about to permanently delete company. This can cause havoc, and is not undoable.')))
			)
		);
	}

	return $form;
}

function intra_company_form_validate($form, &$form_state) {
	if($form_state['values']['c_url'] && !valid_url($form_state['values']['c_url'], true)) {
		form_set_error('c_url', t('Not valid url'));
	}

	if($form_state['values']['c_email'] && !valid_email_address($form_state['values']['c_email'])) {
		form_set_error('c_email', t('Not valid email address'));
	}
}

function intra_company_form_delete_submit($form, &$form_state) {
	$c_id = arg(2);
	if(!is_numeric($c_id)) {
		form_set_error('delete', t('Company ID is missing?'));
		return false;
	}
	try {
		$company = Company::load($c_id);
		$company->delete();
		$company->dbCommit();

		Intra_CMS()->message($company, '!user removed company !company', array(
				'!user'		=> (string) intra_api_view(intra_api_currentuser()),
				'!company'	=> (string) intra_api_view($company)
		));

		if(!$form_state['redirect'])
			$form_state['redirect'] = '<front>';

	} catch(Exception $e) {
		Intra_CMS()->dfb($e, 'Company remove exception');
		form_set_error('delete', t('Company removal failed.'));

		watchdog(INTRA_API_NAME, '%user failed to remove company %company: %message', array(
			'%user' => intra_api_currentuser()->get('p_user'),
			'%company' => $company->get('c_cname'),
			'%message' => $e->getMessage()
		), WATCHDOG_ERROR);
	}
}

function intra_company_form_visibility_toggle($form, &$form_state) {
	$c_id = arg(2);
	$company = Company::load($c_id);

	$set = !$company->get('visible');
	if($set) {
		$msg = t('!company is now <em>visible</em>.', array(
			'!company' => (string) intra_api_view($company)
		));
		drupal_set_message($msg);
	} else {
		$msg = t('!company is now <em>hidden</em>.', array(
			'!company' => (string) intra_api_view($company)
		));
		drupal_set_message($msg);
	}


	Intra_CMS()->message($company, '!user changed company visibility; !message', array(
		'!user' => (string) intra_api_view(intra_api_currentuser()),
		'!message' => $msg
	));

	$form_state['values']['visible'] = $set;
	return intra_company_form_submit($form, $form_state);
}

/**
 * Save company changes
 */
function intra_company_form_submit($form, &$form_state) {
	$c_id = arg(2);
	if(is_numeric($c_id)) {
		$company = Company::load($c_id);
	} elseif($c_id == 'add' && $_GET['id'] && isset($_SESSION['intra_company']['add'][$_GET['id']])) {
		$company = unserialize($_SESSION['intra_company']['add'][$_GET['id']]);
	} else {
		throw new Exception('Unknow company to save!');
	}

	try {
		// Compare old name to new name
		$c_cname = $company->get('c_cname');
		$nameChanged = false;

		intra_api_object_merge($company, $form_state['values']);

		if($c_cname != $form_state['values']['c_cname']) {
			$percent = 0;
			similar_text($c_cname, $form_state['values']['c_cname'], $percent);
			if($percent < 80) {
				$company->set('c_oldname', $c_cname);
				$nameChanged = true;
			}
		}

		intra_api_geocode($company, true);
		$company->save();

		$company->dbCommit();

	} catch( Exception $e ) {
		$company->dbRollback();
		form_set_error('', t('Error while saving company: %err', array('%err' => $e->getMessage())));
		watchdog(INTRA_API_NAME, 'Error whuile saving company: %err', array('%err' => $e->getMessage()), WATCHDOG_ERROR);
		return;
	}

	$message = ($company->get('loadedFromDb')) ? '!user edited company !company' : '!user created a new company !company';
	$replacements = array(
		'!user' => (string) intra_api_view(intra_api_currentuser()),
		'!company' => (string) intra_api_view($company),
	);

	Intra_CMS()->message($company, $message, $replacements);


	$cid = 'Intra:Company:'.$company->get('c_id').':';
	cache_clear_all($cid, 'cache', true);

	if($nameChanged)
		drupal_set_message(t('Company name changed'));

	if(!$form_state['redirect'])
		$form_state['redirect'] = intra_api_url($company);

}

function intra_company_validate_discount($element, &$form_state) {
	if(empty($element['#value']) && $element['#required'] == false)
		return true;

	if(!is_numeric($element['#value'])) {
		form_error($element, t('Needs to be numeric value.'));
	} elseif($element['#value'] < 0) {
		form_error($element, t('Can\'t be lower than zero.'));
	} elseif($element['#value'] > 100) {
		form_error($element, t('Can\'t be higher than 100.'));
	}
}

function intra_company_validate_vatid($element, &$form_state) {
	if(empty($element['#value']) && $element['#required'] == false)
		return true;

	$status = intra_company_vatid($element['#value'], false);
	if($status['status'] == 'error') {
		form_error($element, t('Validation from VAT ID registry failed.'));
	}
}

/**
 * @ingroup Preprocess
 * @{
 */

function template_preprocess_intra_company_details_lite(&$variables) {
	$path = drupal_get_path('module', 'intra_company');
	drupal_add_css($path.'/css/company-details.css');
	$variables['view'] = intra_api_view($variables['company']);
	$variables['visible'] = $variables['company']->get('visible');
}

function template_preprocess_intra_company_details(&$variables) {
	$path = drupal_get_path('module', 'intra_company');
	drupal_add_css($path.'/css/company-details.css');

	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.tabs.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.accordion.css', 'module', 'screen');
		jquery_ui_add(array('ui.tabs', 'effects.core', 'effects.transfer'));
	}

	$variables['view'] = intra_api_view($variables['company']);
	$variables['visible'] = $variables['company']->get('visible');

	$group = $variables['company']->get('c_parent_c_id');
	if($group) {
		$variables['group'] = intra_api_view(Company::load($group));
	}
}

function template_preprocess_intra_company_infobox(&$variables) {
	$variables['view'] = intra_api_view($variables['company']);
	drupal_add_css(drupal_get_path('module', 'intra_api').'/css/infobox.css', 'module');
}

function template_preprocess_intra_company_block(&$variables) {
	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.accordion.css', 'module', 'screen');
		jquery_ui_add(array('ui.accordion'));
		drupal_add_js('Drupal.behaviors.companyBlock = function(context) { $(".company-infobox-block", context).accordion();};', 'inline');
	}
	drupal_add_css(drupal_get_path('module', 'intra_api').'/css/infobox.css', 'module');
	drupal_add_css(drupal_get_path('module', 'intra_company').'/css/company-block.css', 'module');
}
/**
 * @}
 */