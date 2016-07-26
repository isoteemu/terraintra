<?php

function intra_contact_page_infobox(Person $contact) {
	// Allow browsers to cache this.

	if(module_exists('intra_activity')) {
		$activity = intra_activity($contact);
		$activity = array_slice($activity, 0, variable_get('intra_activity_limit_tooltip', 8));
	} else {
		$activity = null;
	}

	echo theme('intra_contact_infobox', $contact, $activity);
}

function intra_contact_page_view(Person $contact) {

	drupal_add_css(drupal_get_path('module', 'intra_contact').'/css/contact-details.css');

	drupal_set_title($contact->get('p_fname').' '.$contact->get('p_lname'));
	$o = theme('intra_contact_details', $contact);

	if(module_exists('intra_activity')) {
		$me = intra_api_currentuser();
		if($me->get('p_id') != $contact->get('p_id') && $me->get('c_id') == $contact->get('c_id')) {
			drupal_set_message(t('Activity disabled while viewing own company.'), 'warning');
		} else {
			$activity = intra_activity($contact);
			if(count($activity))
				$o .= theme('intra_activity_events', $activity);
		}
	}

	return $o;
}


function intra_contact_page_add() {
	$page = '';
	if($_GET['id'] && isset($_SESSION['intra_contact']['add'][$_GET['id']])) {

		$contact = unserialize($_SESSION['intra_contact']['add'][$_GET['id']]);
		$page .= intra_contact_page_edit($contact);
	} else {
		$page .= drupal_get_form('intra_contact_form_add');
	}

	return $page;
}

/**
 * Contact editing page
 */
function intra_contact_page_edit(Person $contact) {

	drupal_add_css(drupal_get_path('module', 'intra_contact').'/css/contact-details.css');
	drupal_add_css(drupal_get_path('module', 'intra_contact').'/css/contact-edit.css');
	return drupal_get_form('intra_contact_form', $contact);
}

function intra_contact_page_dnd(Person $contact) {
	$accessMode = intra_api_accessmode();
	intra_api_accessmode(INTRA_API_ACCESSMODE_NONE);
	$personMode = $contact->getMode();
	$contact->getMode(Person::GETMODE_RECURSE);

	$view = intra_api_view($contact);
	$row = array();

	if($contact->get('p_title'))
		$row[] = array(t('Title').':', (string) $view->get('p_title'));
	if($contact->get('p_class'))
		$row[] = array(t('Taxonomy').':', (string) $view->get('p_class'));

	$row[] = array(array('data' => '', 'colspan' => 2));

	$row[] = array(t('Company').':', (string) $view->getCompany());

	$row[] = array(array('data' => '', 'colspan' => 2));

	if($contact->get('p_email'))
		$row[] = array(t('Email').':', $view->get('p_email'));
	if($contact->get('p_skype'))
		$row[] = array(t('Skype').':', $view->get('p_skype'));
	if($contact->get('p_phone'))
		$row[] = array(t('Phone').':', $view->get('p_phone'));
	if($contact->get('p_telefax'))
		$row[] = array(t('Fax').':', $view->get('p_telefax'));

	$row[] = array(array('data' => '', 'colspan' => 2));

	if($contact->get('p_street'))
		$row[] = array(t('Street').':', $view->get('p_street'));
	if($contact->get('p_box'))
		$row[] = array(t('P.O. Box').':', $view->get('p_box'));
	if($contact->get('p_zip'))
		$row[] = array(t('Zip').':', $view->get('p_zip'));
	if($contact->get('p_city'))
		$row[] = array(t('City').':', $view->get('p_city'));
	if($contact->get('p_country'))
		$row[] = array(t('Country').':', $view->get('p_country'));

	$caption = '<h3 style="font-size:110%; font-weight:bold;">'.$view.'</h3>';

	echo theme('table', null, $row, array(
		'class' => 'vcard contact'
	), $caption);

	// Restore modes
	$contact->getMode($personMode);
	intra_api_accessmode($accessMode);
}

/**
 * Votes contact.
 * This data then can be used to sort favorite contacts.
 */
function intra_contact_page_ping(Person $contact) {
	$r = intra_contact_vote($contact);
	echo drupal_json($r);
}

/**
 * Add new contact -form
 * @ingroup Form
 */
function intra_contact_form_add(&$form_state) {
	$form = array();
	$form['set'] = array(
		'#type' => 'fieldset',
		'#title' => t('Add new contact')
	);

	$form['set']['contact'] = array(
		'#type' => 'textfield',
		'#title' => t('Contact name'),
		'#description' => implode("\n", module_invoke_all('help', 'intra/contact/add#contact', drupal_help_arg())),
		'#default_value' => $_GET['contact']
	);

	$c_id = '';
	if($_GET['c_id'] && is_numeric($_GET['c_id']) && Company::load($_GET['c_id']) instanceOf Company)
		$c_id = $_GET['c_id'];

	$form['c_id'] = array(
		'#type' => 'hidden',
		'#value' => $c_id,
	);

	if($_GET['p_email']) {
		$form['p_email'] = array(
			'#type' => 'hidden',
			'#value' => $_GET['p_email']
		);
	} elseif($_GET['email']) {
		$form['p_email'] = array(
			'#type' => 'hidden',
			'#value' => $_GET['email']
		);
	}

	if($_GET['p_class']) {
		$form['p_class'] = array(
			'#type' => 'hidden',
			'#value' => (string) new Person_Class($_GET['p_class'])
		);
	}

	if($_GET['p_phone']) {
		$form['p_phone'] = array(
			'#type' => 'hidden',
			'#value' => $_GET['p_phone']
		);
	}

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

function intra_contact_form_add_validate($form, &$form_state) {

	if(!empty($form_state['values']['c_id'])) {
		if(!is_numeric($form_state['values']['c_id'])) {
			form_set_error('c_id', t('Company ID must be number'));
		} elseif(! Company::load($form_state['values']['c_id']) instanceOf Company ) {
			form_set_error('c_id', t('Company ID was not found.'));
		}
	}
}

function intra_contact_form_add_submit($form, &$form_state) {

	$contact = Person::factory('Person');

	if($form_state['values']['c_id']) {
		$contact->set('c_id', $form_state['values']['c_id']);
	}

	if($form_state['values']['p_email'] && valid_email_address($form_state['values']['p_email'])) {
		$contact->set('p_email', $form_state['values']['p_email']);
	}

	if($form_state['values']['p_class']) {
		$class = (array) new Person_Class($form_state['values']['p_class']);
		$codes = array_keys(Codes::arrayMap('P_CLASS'));
		$p_class = array_intersect($codes, $class);
		$contact->set('p_class', $p_class);
	}

	module_invoke_all('intra_detect_contact', $contact, $form_state['values']['contact']);

	// Emails are regarded as unique
	$check = false;
	if($email = $contact->get('p_email')) {
		$check = Person::load(array(
			'%p_email' => $email,
			'loadedFromDb' => 1
		));
	}
	if($check && count($check)) {
		drupal_set_message(t('Contact with email %email was found.', array('%email' => $email)));
		$form_state['redirect'] = intra_api_url($check->current()).'/edit';
	} else {
		$id = $form_state['values']['form_build_id'];

		$_SESSION['intra_contact']['add'][$id] = serialize($contact);

		$form_state['redirect'] = array(
			'intra/contact/add',
			'id='.drupal_urlencode($id)
		);
	}
}

/**
 * @ingroup Form
 */
function intra_contact_form(&$form_state, $contact) {
	$form = array();

	$form['details'] = array(
		'#type' => 'fieldset',
	);

	$form['details']['name'] = array(
		//'#prefix' => '<div class="container-inline name">',
		//'#suffix' => '</div>'
	);

	$form['details']['name']['p_fname'] = array(
		'#title' => t('First name'),
		'#required' => true,
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_fname')
	);

	$form['details']['name']['p_lname'] = array(
		'#title' => t('Last name'),
		'#required' => true,
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_lname')
	);

	$form['details']['name']['p_title'] = array(
		'#title' => t('Title'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_title')
	);

	$form['details']['name']['p_dname'] = array(
		'#title' => t('Department'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_dname')
	);

	if($form_state['values']['c_id']) {
		$c_id = $form_state['values']['c_id'];
	} else {
		$c_id = $contact->get('c_id');
	}

	$form['details']['name'] += intra_company_formelement('c_id');
	$form['details']['name']['c_id']['#default_value'] = ($c_id) ? $c_id : '';
	$form['details']['name']['c_id']['#required'] = true;

	$form['tabs'] = array(
		'#theme' => 'fielset_tabbed'
	);

	$form['tabs']['details'] = array(
		'#title' => t('Contact'),
		'#type' => 'fieldset'
	);

	$emails = $contact->getEmails();
	$list = $emails->implodeBy('pe_email');

	$form['tabs']['details']['p_email'] = array(
		'#title' => t('Email'),
		'#type' => 'textfield',
		'#default_value' => $list
	);

	$form['tabs']['details']['p_skype'] = array(
		'#title' => t('Skype'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_skype')
	);

	$form['tabs']['details']['p_phone'] = array(
		'#title' => t('Phone'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_phone')
	);

	$form['tabs']['details']['p_telefax'] = array(
		'#title' => t('Fax'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_telefax')
	);

	$form['tabs']['address'] = array(
		'#title' => t('Address'),
		'#type' => 'fieldset'
	);

	$form['tabs']['address']['p_street'] = array(
		'#title' => t('Street'),
		'#type' => 'textarea',
		'#rows' => 2,
		'#default_value' => $contact->get('p_street')
	);

	$form['tabs']['address']['p_box'] = array(
		'#title' => t('P.O. Box'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_box')
	);

	$form['tabs']['address']['p_zip'] = array(
		'#title' => t('Postcode'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_zip')
	);

	$form['tabs']['address']['p_city'] = array(
		'#title' => t('City'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_city')
	);

	$form['tabs']['address']['p_country'] = array(
		'#title' => t('Country'),
		'#type' => 'select',
		'#options' => _intra_company_countryselect(),
		'#default_value' => $contact->get('p_country'),
		'#attributes' => array(
			'class' => 'countryselect'
		)
	);

	$form['tabs']['taxonomy'] = array(
		'#title' => t('Taxonomy'),
		'#type' => 'fieldset'
	);

	$classes = Codes::arrayMap('P_CLASS');
	$cClass = (array) $contact->get('p_class');

	$form['tabs']['taxonomy']['p_class'] = array(
		'#title' => t('Taxonomy'),
		'#type' => 'select',
		'#multiple' => true,
		'#options' => $classes,
		'#default_value' => $cClass
	);


	$form['tabs']['access'] = array(
		'#title' => t('Access'),
		'#type' => 'fieldset'
	);

	$types = Codes::arrayMap('P_TYPE');
	$type = $contact->get('p_type');
	if(!isset($type)) $type = 2;

	$form['tabs']['access']['p_type'] = array(
		'#title' => t('Type'),
		'#type' => 'select',
		'#options' => $types,
		'#default_value' => $type
	);

	$form['tabs']['access']['p_user'] = array(
		'#title' => t('Username'),
		'#type' => 'textfield',
		'#default_value' => $contact->get('p_user'),
		'#attributes' => array(
			'autocomplete' => 'off'
		)
	);

	$psw  = ($contact->get('p_psw') != null) ? '*****' : '';
	$form['tabs']['access']['p_psw'] = array(
		'#title' => t('Password'),
		'#type' => 'textfield',
		'#default_value' => $psw,
		'#attributes' => array(
			'class' => 'password-field',
			'onfocus' => "if(this.value == '*****') this.value = ''",
			'autocomplete' => 'off'
		),
	);
	if(module_exists('user'))
		_user_password_dynamic_validation();

	$form['tabs']['rem'] = array(
		'#title' => t('Remarks'),
		'#type' => 'fieldset'
	);

	$form['tabs']['rem']['p_rem'] = array(
		'#title' => t('Remarks'),
		'#type' => 'textarea',
		'#rows' => 5,
		'#default_value' => $contact->get('p_rem')
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
	if($contact && $contact->get('loadedFromDb')) {

		$visible = $contact->get('visible');
		$form['buttons']['visibility'] = array(
			'#type' => 'submit',
			'#value' => ($visible) ? t('Hide') : t('Show'),
			'#weight' => 10,
			'#submit' => array('intra_contact_form_visibility_toggle'),
			'#attributes' => array(
				'class' => 'ui-corner-left '.(($visible) ? 'hide' : 'show')
			)
		);

		$form['buttons']['delete'] = array(
			'#type' => 'submit',
			'#value' => t('Delete'),
			'#weight' => 15,
			'#submit' => array('intra_contact_form_delete_submit'),
			'#attributes' => array(
				'class' => 'ui-corner-right',
				'onclick' => sprintf("return confirm('%s');", addslashes(t('You are about to permanently delete contact. This can cause havoc, and is not undoable.')))
			)
		);
	}

	return $form;
}

/**
 * Validate contact edit/add
 * @ingroup Form
 */
function intra_contact_form_validate($form, &$form_state) {
	/// TODO: Save p_id in form
	$p_id = arg(2);

	// Split emails
	$emails = Person_Emailcollection::explode($form_state['values']['p_email']);

	foreach($emails as $email) {
		if(!valid_email_address($email)) {
			form_set_error('p_email', t('Email %email is not valid', array(
				'%email' => $email
			)));
			continue;
		}
		$existing = Person_Email::load(array(
			'%pe_email'		=> $email,
			'loadedFromDb'	=> 1
		))->current();

		if($existing && $existing->get('p_id') != $p_id) {
			$extUser = Person::load($existing->get('p_id'));
			form_set_error('p_email', t('Email %email belongs to !user', array(
				'%email' => $email,
				'!user'	 => (string) intra_api_view($extUser)
			)));
		}
	}

	if(empty($form_state['values']['p_user']) ^ (empty($form_state['values']['p_psw']))) {
		$err = (empty($form_state['values']['p_user'])) ? 'p_user' : 'p_psw';
		form_set_error($err, t('Both username and password are required'));
	} elseif(!empty($form_state['values']['p_user'])) {

		if(module_exists('user') && $err = user_validate_name($form_state['values']['p_user'])) {
			form_set_error('p_user', $err);
		} else {
			// Check for uniqueness
			$existing = Person::load(array(
				'%p_user' => $form_state['values']['p_user'],
				'loadedFromDb' => true
			))->current();
			if($existing && $existing->get('p_id') != arg(2)) {
				form_set_error('p_user', t('Account name is in use by !person', array(
					'!person' => (string) intra_api_view($existing)
				)));
			}
		}
	}

}

/**
 * Remove contact.
 */
function intra_contact_form_delete_submit($form, &$form_state) {
	$p_id = arg(2);
	if(!is_numeric($p_id)) {
		form_set_error('delete', t('Contact ID is missing?'));
		return false;
	}
	try {
		$contact = Person::load($p_id);
		$contact->delete();
		$contact->dbCommit();

		Intra_CMS()->message($contact, '!user removed contact !person from !company', array(
			'!user' => (string) intra_api_view(intra_api_currentuser()),
			'!person' => (string) intra_api_view($contact),
			'!company' => (string) intra_api_view($contact->getCompany())
		));

		if(!$form_state['redirect'])
			$form_state['redirect'] = '<front>';

	} catch(Exception $e) {
		Intra_CMS()->dfb($e, 'Contact remove exception');
		form_set_error('delete', t('Contact removal failed.'));

		watchdog(INTRA_API_NAME, '%user tried to remove contact %fname %lname from %company, but it failed: %message', array(
			'%user' => intra_api_currentuser()->get('p_user'),
			'%fname' => $contact->get('p_fname'),
			'%lname' => $contact->get('p_lname'),
			'%company' => $contact->getCompany()->get('c_cname'),
			'%message' => $e->getMessage()
		), WATCHDOG_ERROR);
	}
}

function intra_contact_form_visibility_toggle($form, &$form_state) {
	$c_id = arg(2);
	$contact = Person::load($c_id);

	$set = !$contact->get('visible');
	if($set) {
		$msg = t('!person is now <em>visible</em>.', array(
			'!person' => (string) intra_api_view($contact)
		));
	} else {
		$msg = t('!person is now <em>hidden</em>.', array(
			'!person' => (string) intra_api_view($contact)
		));
	}

	Intra_CMS()->message($contact, '!user changed contact visibility from company !company: !message', array(
		'!user' => (string) intra_api_view(intra_api_currentuser()),
		'!company' => (string) intra_api_view($contact->getCompany()),
		'!message' => $msg
	));

	$form_state['values']['visible'] = $set;
	return intra_contact_form_submit($form, $form_state);
}

/**
 * Save/Add contact.
 * @todo Save ID in form.
 * @ingroup Form
 */
function intra_contact_form_submit($form, &$form_state) {
	$id = arg(2);

	if(is_numeric($id)) {
		$contact = Person::load($id);
	} elseif($id == 'add' && $_GET['id'] && isset($_SESSION['intra_contact']['add'][$_GET['id']])) {
		$contact = unserialize($_SESSION['intra_contact']['add'][$_GET['id']]);
	} else {
		throw new Exception('Unknow company to save!');
	}

	try {
		$sent_emails = Person_Emailcollection::explode($form_state['values']['p_email']); 
		$primary_email = $sent_emails[0];
		unset($form_state['values']['p_email']);

		if($form_state['values']['p_psw'] == '*****') {
			// Don't change password
			unset($form_state['values']['p_psw']);
		}

		intra_api_object_merge($contact, $form_state['values']);

		// Copy contact details to company
		$company = Company::load($form_state['values']['c_id']);
		if(!$company->get('loadedFromDb')) {
			$company->add('c_email', $primary_email);

			module_invoke_all('intra_detect_company', $company);

			$company->add('c_country', $contact->get('p_country'));
			$company->add('c_phone', $contact->get('p_phone'));
			$company->add('c_telefax', $contact->get('p_telefax'));

			$company->add('c_rem', t('part of contact @fname @lname @date/@user', array(
				'@fname' => $contact->get('p_fname'),
				'@lname' => $contact->get('p_lname'),
				'@date' => format_date(time(), 'small'),
				'@user' => intra_api_currentuser()->get('p_user')
			))); // line-break

			$company->save();
			$contact->set('c_id', $company->get('c_id'));

			Intra_CMS()->message($company, '!user created a new company !company', array(
				'!user' => (string) intra_api_view(intra_api_currentuser()),
				'!company' => (string) intra_api_view($company)
			));
		}

		// Delete removed emails
		$emails = $contact->getEmails();
		foreach($emails->getChildren() as $id => $emailObj) {
			$email = $emailObj->get('pe_email');
			$pos = array_search($email, $sent_emails);
			if($pos !== false) {
				unset($sent_emails[$pos]);
			} else {
				$emailObj->delete();
				unset($emails[$id]);
			}
		}

		// Add new emails
		foreach($sent_emails as $email) {
			if(empty($email)) continue;
			$emails[] = Person_Email::factory('Person_Email', array(
				'pe_email' => $email
			));
		}

		$contact->set('p_email', $primary_email);

		$contact->save();
		$contact->dbCommit();
		$message = ($contact->get('loadedFromDb')) ? '!user edited contact !person from !company' : '!user create new contact !person for !company';
		Intra_CMS()->message($contact, $message, array(
			'!user' => (string) intra_api_view(intra_api_currentuser()),
			'!person' => (string) intra_api_view($contact),
			'!company' => (string) intra_api_view($contact->getCompany()),
		));

	} catch( Exception $e ) {
		$contact->dbRollback();
		form_set_error('', t('Error while saving contact: %err', array('%err' => $e->getMessage())));

		watchdog(INTRA_API_NAME, '%user failed to save contact %fname %lname from company %company: %message', array(
			'%user' => intra_api_currentuser()->get('p_user'),
			'%fname' => $contact->get('p_fname'),
			'%lname' => $contact->get('p_lname'),
			'%company' => $contact->getCompany()->get('c_cname'),
			'%message' => $e->getMessage()
		), WATCHDOG_ERROR);

		return;
	}

	$cid = 'Intra:Person:'.$contact->get('p_id').':';
	cache_clear_all($cid, 'cache', true);

	if(!$form_state['redirect'])
		$form_state['redirect'] = intra_api_url($contact);
}

/**
 * @ingroup Preprocess
 * Template Preprocess functions
 * @{ 
 */

function template_preprocess_intra_contact_email(&$variables) {
	$variables['view'] = intra_api_view($variables['email']);
}

function template_preprocess_intra_contact_select(&$variables) {
	$path = drupal_get_path('module', 'intra_contact');
	drupal_add_css($path.'/css/contact-select.css');
	drupal_add_js($path.'/js/contact-select.js');

	drupal_add_js('misc/collapse.js');

	$variables['groups'] = array();

	$variables['contacts']->sortChildren('p_lname');

	$favCount = 5;
	if($favCount < count($variables['contacts']) && module_exists('votingapi')) {
		$variables['favorites'] = array();
		foreach($variables['contacts'] as $person) {
			$votes = intra_contact_get_votes($person);
			if($votes)
				$variables['favorites'][$person->get('p_id')] = $votes;
		}

		arsort($variables['favorites'], SORT_NUMERIC);
		$variables['favorites'] = array_slice($variables['favorites'], 0, $favCount, true);

	}

	// Build codes
	$codes = Codes::arrayMap('P_CLASS');
	foreach($variables['contacts'] as $contact) {
		foreach($contact->get('p_class') as $group) {
			$variables['groups'][$group]++;
		}
	}
}

function template_preprocess_intra_contact_infobox(&$variables) {
	$variables['view'] = intra_api_view($variables['contact']);
	drupal_add_css(drupal_get_path('module', 'intra_api').'/css/infobox.css', 'module');
}

function template_preprocess_intra_contact_details(&$variables) {
	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.tabs.css', 'module', 'screen');
		jquery_ui_add(array('ui.tabs'));
	}

	$variables['view'] = intra_api_view($variables['contact']);
}

/**
 * @} End of Preprocess
 */

/**
 * @ingroup Theme
 * Simple theme functions
 * @{
 */

function theme_intra_contact($contact) {
	if(is_numeric($contact)) {
		$contact = intra_contact_load($contact);
		if($contact)
			$view = intra_api_view($contact);
	} elseif( $contact instanceOf Person ) {
		$view = intra_api_view($contact);
	}

	if($view)
		return ((string) $view);
	return null;
}

function theme_intra_contact_vcard_download(Person $contact) {
	$a  = '<a type="text/x-vcard" href="'.url(intra_api_url($contact).'/vcard.vcf').'" class="vcard-download" title="'.t('Download vcard').'">';
	$a .= theme('image', drupal_get_path('module', 'intra_api').'/image/vcard.png');
	$a .= '</a>';
	return $a;
}

/**
 * @} End of Theme
 */