<?php
require_once('class.inc.php');
require_once('price_calculator.class.inc.php');
require_once('intra.functions.inc.php');

function invoicefactory_menu($may_cache) {
	$items = array();

	if(!isset($_SESSION['bundle_calculator']['menuid']))
		$_SESSION['bundle_calculator']['menuid'] = 'price_calculator';

	if(!isset($_SESSION['bundle_calculator']['auth']))
		$_SESSION['bundle_calculator']['auth'] = false;

	if (!$may_cache) {
		if($_SESSION['bundle_calculator']['customer']) {
			$items[] = array(
				'title' => t('Create Invoice'),
				'path' => 'distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory',
				'access' => $_SESSION['bundle_calculator']['auth'],
				'callback' => 'invoicefactory_page_invoice',
				'type' => MENU_LOCAL_TASK
			);
			$items[] = array(
				'title' => t('Create Invoice'),
				'path' => 'distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.arg(5).'/commit',
				'access' => $_SESSION['bundle_calculator']['auth'],
				'callback' => 'invoicefactory_page_commit',
				'type' => MENU_LOCAL_TASK
			);
			$items[] = array(
				'title' => t('Invoice Created'),
				'path' => 'distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.arg(5).'/saved',
				'access' => $_SESSION['bundle_calculator']['auth'],
				'callback' => 'invoicefactory_page_createdinvoice',
				'type' => MENU_LOCAL_TASK
			);
			$items[] = array(
				'title' => t('PDF'),
				'path' => 'distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.arg(5).'/invoice.pdf',
				'access' => $_SESSION['bundle_calculator']['auth'],
				'callback' => 'invoicefactory_download_invoice_pdf',
				'type' => MENU_LOCAL_TASK
			);
		}
	}
	return $items;
}

function invoicefactory_page_invoice($in_id=null) {
	theme('bc_webapp');

	theme_add_style(drupal_get_path('module', 'bundle_calculator') . '/bundle_calculator.css');
	theme_add_style(drupal_get_path('module', 'invoicefactory') . '/intra/css/invoicefactory.css');
	drupal_add_js(drupal_get_path('module', 'invoicefactory') . '/intra/js/terra.intra.pdfViewer.js?'.filemtime('modules/teemu/intra/js/terra.intra.pdfViewer.js'));

	$o  = '<div id="invoicefactory">';

	$invoice = invoice_build($in_id);


	invoicefactory_current_in_id($invoice->get('id'));

	$form = array(
		'#action' => url('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$invoice->get('id'))
	);

	$form['in_id'] = array(
		'#type' => 'value',
		'#value' => $invoice->get('id')
	);

	$form['billing'] = intra_form_field_customer_select();
	$form['billing']['#default_value'] = $invoice->billing()->get('c_id');
	$form['billing']['#description'] = t('Billing company');

	$form['customer'] = intra_form_field_customer_select();
	$form['customer']['#default_value'] = $invoice->customer()->get('c_id');
	$form['customer']['#description'] = t('End-customer company');

	$form['invoice'] = array();
	$form['invoice']['nr'] = array(
		'#type' => 'textfield',
		'#title' => t('Invoice Number'),
		'#default_value' => $invoice->get('in_nr'),
		'#description' => t('Invoice number. Leave to empty or to default (%default) to allocate invoice number automaticly', array('%default' => INTRA_INVOICE_DRAFT))
	);

	$ag = Agreement::load(array('se_c_id' => $invoice->customer()->get('c_id')));
	if($ag->count() && $ag->get('ag_status') == 0) {
		$form['invoice']['ag_nr'] = array(
			'#type' => 'markup',
			'#title' => t('Agreement #'),
			'#value' => check_plain($ag->get('ag_nr')),
			'#description' => t('Maintenance agreement number')
		);
	}

	$form['invoice']['invoice_date'] = array(
		'#type' => 'textfield',
		'#title' => t('Invoice Date'),
		'#default_value' => date('Y-m-d', strtotime($invoice->get('in_invoice_date'))),
		'#attributes' => array('class' => 'jscalendar'),
		'#jscalendar_ifFormat' => '%Y-%m-%d',
		'#jscalendar_showsTime' => 'false',
		'#description' => t('Day when invoice is created - i.e. now. Must be in ISO -format (YYYY-MM-DD)')
	);
	$form['invoice']['cust_reference'] = array(
		'#type' => 'textfield',
		'#title' => t('Purchase Order'),
		'#default_value' => $invoice->get('in_cust_reference'),
		'#description' => t('Customer Reference number - if any')
	);
	$form['invoice']['order_date'] = array(
		'#type' => 'textfield',
		'#title' => t('Purchase Order Date'),
		'#default_value' => ($od = strtotime($invoice->get('in_order_date'))) ? date('Y-m-d', $od) : '',
		'#attributes' => array('class' => 'jscalendar'),
		'#jscalendar_ifFormat' => '%Y-%m-%d',
		'#jscalendar_showsTime' => 'false',
		'#description' => t('Date when purchase order was issued. Must be in ISO -format (YYYY-MM-DD)')
	);

	$form['invoice']['contact'] = array(
		'#type' => 'select',
		'#title' => t('Contact'),
		'#options' => array(),
		'#required' => false,
		'#description' => t('Contact person for invoice. If person is defined, his/her address is used to send invoice')
	);
	$people = $invoice->billing()->people();
	foreach($people->getChildren() as $person) {
		$form['invoice']['contact']['#options'][implode(', ',(array) $person->get('p_class'))][$person->get('p_id')] = sprintf('%s %s', $person->get('p_fname'), $person->get('p_lname'));
	}
	if($p_id = $invoice->get('p_id')) {
		$form['invoice']['contact']['#default_value'] = $p_id;
	} else {
		$account = $people->each()->filter(array('p_class' => 'Account'));
		if($account->count())
			$form['invoice']['contact']['#default_value'] = $account->get('p_id');
	}

	if($vat = $invoice->billing()->get('c_vat')) {
		$form['invoice']['cust_vat'] = array(
			'#type' => 'markup',
			'#title' => t('Vat ID'),
			'#value' => $vat,
			'#description' => t('Billing company VAT ID. Fetched from Intra, if is set')
		);
	}

	//
	// Handle invoice items
	//

	$bundles = $invoice->prices()->bundles();
	if($bundles->count()) {
		foreach($bundles->getChildren() as $bundle) {
			$item = array();
			$item['rem'] = array();
			$item['rem'][]['#value'] = $bundle->get('pr_name');
			$item['serials'] = array();
			$item['rate']['#value'] = $bundle->get('rate');
			$item['discount']['#value'] = $bundle->get('se_discount');
			$item['fee']['#value'] = $bundle->get('se_fee');

			foreach($bundle->getChildren() as $sub) {
				$item['rem'][]['#value'] = $sub->get('pr_name');
				$item['serials'][]['#value'] = $sub->get('se_serial');
			}

			$form['items'][] = $item;
		}
	}

	$additional = $invoice->prices()->additional();
	if($additional->count()) {
		foreach($additional as $bundle) {
			$item = array();
			$item['rem'] = array();
			$item['rem'][]['#value'] = $bundle->get('pr_name');
			$item['serials'][]['#value'] = $bundle->get('se_serial');
			$item['rate']['#value'] = $bundle->get('rate');
			$item['discount']['#value'] = $bundle->get('se_discount');
			$item['fee']['#value'] = $bundle->get('se_fee');

			$form['items'][] = $item;
		}
	}

	$total = $invoice->prices()->total();

	if($invoice->prices()->periodLength() > 0 && $invoice->prices()->maintenancePrice() > 0) {
		$form['additional']['maintenance'] = array(
			'#title' => t('Maintenance fee from %from to %to', array('%from' => $invoice->prices()->periodFrom(), '%to' => $invoice->prices()->periodTo())),
			'#value' => $invoice->prices()->maintenancePrice()
		);
	}

	if($invoice->billing()->get('c_type') == 1) {
		$percent = $invoice->billing()->get('c_discount');
		$disc = $total * ($percent/100);
		$total -= $disc;

		$form['additional']['distributor'] = array(
			'#title' => t('Distributor discount %percent %', array('%percent' => $percent)),
			'#value' => $disc
		);
	}

	if($invoice->get('in_type') == Invoice::TYPE_MAINTENANCE)
		$nid = INTRA_INVOICE_REM_MAINTENANCE;
	else
		$nid = INTRA_INVOICE_REM_DEFAULT;

	$rem = _lataus20_node_load_t($nid);
	$form['rem'] = array(
		'#type' => 'textarea',
		'#default_value' => $invoice->get('in_rem'),
		'#description' => t('Free text, which is added into invoice before listing. Please note that only small subset of HTML standard is supported, and layout will not be exact copy. To edit default text, see: "<a href="%url">%title</a>"', array(
			'%url' => url('node/'.$rem->nid),
			'%title' => $rem->title
		))
	);

	$vat = $invoice->get('in_vat_pros');
	$vatsum = 0;
	if($vat > 0) {
		$vatsum = $total*($vat/100);
		//$total += $vatsum;
	};
	$form['additional']['vat'] = array(
		'#title' => t('VAT %percent %', array('%percent' => $vat)),
		'#value' => $vatsum
	);

	$form['fee'] = array(
		'#type' => 'value',
		'#value' => $total
	);

	$currency = intra_currency_from_intra($invoice->get('in_currency'));

	$form['currency'] = array(
		'#type' => 'select',
		'#default_value' => $currency,
		'#options' => currency_api_get_list()
	);

	$form['rate'] = array(
		'#type' => 'value',
		'#title' => t('Currency Rate'),
		'#value' => intra_currency_rate($invoice->get('in_currency'))
	);

	$form['due_date'] = array(
		'#type' => 'textfield',
		'#default_value' => date('Y-m-d', strtotime($invoice->get('in_due_date')))
	);

	$payment = _lataus20_node_load_t(INTRA_INVOICE_REM_PAYMENT);
	$form['payment'] = array(
		'#type' => 'textarea',
		'#default_value' => $payment->body,
		'#description' => t('Free text, which is added into invoice after listing - like banking information etc. Please note that only small subset of HTML standard is supported, and layout will not be exact copy. To edit default text, see: "<a href="%url">%title</a>"', array(
			'%url' => url('node/'.$payment->nid),
			'%title' => $payment->title
		))
	);

	$form['a-ok'] = array(
		'#type' => 'fieldset',
		'#attributes' => array(
			'class' => 'noprint'
		)
	);

	if($_SESSION['bundle_calculator']['PERSON']['C_TYPE'] == 1) {
		// Distributor
		$description = 'Create new Purchase Order';
	} elseif(isset($_SESSION['bundle_calculator']['PERSON']['C_TYPE']) && $_SESSION['bundle_calculator']['PERSON']['C_TYPE'] == 0) {
		// Admin
		$description = 'Accept and create new invoice';
	} else {
		trigger_error('Unknown company type.', E_USER_ERROR);
	}

	$form['a-ok']['preview'] = array(
		'#type' => 'submit',
		'#value' => t('Preview'),
		'#name' => 'preview',
		'#attributes' => array(
			'class' => 'login-box-submit pdfViewer',
			'style' => 'clear:both;'
		),
		'#description' => t('Show preview PDF file of invoice. Some attributes, like invoice number, are not set'),
		'#id' => 'preview-pdf',
	);

	$form['a-ok']['save'] = array(
		'#prefix' => '<div class="box-inline">'.$description.'</div>',
		'#type' => 'submit',
		'#value' => t('Create'),
		'#name' => 'save',
		'#attributes' => array(
			'class' => 'login-box-submit',
			'style' => 'clear:both;'
		)
	);
	$o .= drupal_get_form('invoice_form_invoice', $form);

	$o .= '</div>';
	return $o;
}

function invoicefactory_page_commit() {
	$in_id = arg(5);
	if($in_id && isset($_SESSION['bundle_calculator']['invoices'][$in_id])) {
		invoicefactory_current_in_id($in_id);
		teemu_dmesg('setting path '.invoicefactory_current_in_id());
	} else {
		drupal_page_not_found();
		return;
	}

	$invoice = invoice_build();
	$path = variable_get('intra_file_path', '/srv/terraintra/files');

	// Create PDF
	try {

		$in_nr = $invoice->allocateInvoice();
		$filename = sprintf('%d_IN_ID.pdf', $invoice->get('id'));

		// Format REM message
		$rem = array(
			'$periodFrom' => $invoice->prices()->periodFrom(),
			'$periodTo' => $invoice->prices()->periodTo()
		);
		foreach($invoice->attributes() as $key) {
			$rem[sprintf('$%s', $key)] = $invoice->get($key);
		}

		$formated = strtr($invoice->get('in_rem'), $rem);
		$payment = strtr($invoice->get('payment'), $rem);

		$invoice->set('in_rem', $formated);
		$invoice->set('payment', $payment);

		// If using special GeoCue rate, convert to USD
		if($invoice->get('in_currency') == 'GEO') {
			$invoice->set('in_currency', 'USD');
		}

		$pdf = intra_invoice_pdf($invoice);
		$invoice->set('x_file', $filename);

		$invoice->save();

		// Update agreement ending date
		if($invoice->get('in_type') == Invoice::TYPE_MAINTENANCE) {

			$serials = $invoice->articles(array('se_type' => Intra_Product_Agreement::SE_TYPE))->each()->get('se_serial')->getChildren();
			$serials = array_filter($serials);
			foreach($serial as $ag_nr) {
				$ag = Agreement::load(array('ag_nr' => $invoice->customer()->get('c_id')))->current();
				if($ag) {
					// TODO: Should take from serial
					list($ag_month, $ag_year) = explode('/', $invoice->prices()->periodTo());
					$ag_time = mktime(0,0,0,$ag_month+1,0,$ag_year);
					$ag->set('ag_date3', date('c', $ag_time));
					$ag->save();
				}
			}
		}

		// Got here...
		rename($pdf, $path.'/'.$filename);
		$invoice->dbCommit();
	} catch(Exception $e) {
		$invoice->dbRollback();

		drupal_set_message(t('Failed to save invoice.'), 'error');
		watchdog('TerraIntra', 'Failed to save invoice. Error: '.$e->getMessage().' line:'.$e->getLine(), WATCHDOG_ERROR);
		Intra_CMS()->dfb($e);

		drupal_goto('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$invoice->get('id'));
		return '';
	}

	drupal_set_message(t('Invoice %in_nr created.', array('%in_nr' => $in_nr)));

	drupal_goto('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$invoice->get('id').'/saved');

}

function invoicefactory_page_createdinvoice() {
	$in_id = arg(5);
	if($in_id && isset($_SESSION['bundle_calculator']['invoices'][$in_id])) {
		invoicefactory_current_in_id($in_id);
		teemu_dmesg('setting path '.invoicefactory_current_in_id());
	} else {
		drupal_page_not_found();
		return;
	}

	theme('bc_webapp');

	theme_add_style(drupal_get_path('module', 'bundle_calculator') . '/bundle_calculator.css');
	theme_add_style(drupal_get_path('module', 'invoicefactory') . '/intra/css/invoicefactory.css');
	$invoice = Invoice::load(invoicefactory_current_in_id());

	$agreement = Agreement::load(array('se_c_id' => $invoice->customer()->get('c_id')));
	if(!$agreement->count() && ($invoice->articles()->each()->filter(array('%se_agreement' => 'X'))->count() > 0 || $invoice->customer()->isAcademic())) {
		drupal_set_message(t('Obs. Company has no maintenance agreement. You should create one now.'), 'error');
	}

	$send = 'https://www.example.com/terraintra/admin/email.asp' .
		'?Action=EMAIL&C_ID='.urlencode($invoice->billing()->get('c_id')).
		'&USERID='.urlencode($_SESSION['bundle_calculator']['PERSON']['P_ID']).
		'&X_ID='.urlencode($invoice->get('in_id')).
		'&X_VAR=IN_ID&X_FILE='.urlencode($invoice->get('x_file')).
		'&AC_TYPE=8&AC_NR='.urlencode($invoice->get('in_nr'));

	return '<h2> Invoice ID: '.$invoice->get('in_nr').'</h2>'.
			'<div><a href="/terraintra/files/'.urlencode($invoice->get('x_file')).'">View PDF</a></div>'.
			'<div><a href="'.$send.'" onclick="window.open(\''.$send.'\',\'Invoice\',\'scrollbars=yes,resizable=yes,width=550,height=530\'); return false;">Send Invoice</a></div>';
}

function invoice_form_invoice_submit($form_id, $form) {
	$invoice =& invoice::load($form['in_id']);

	if($form['customer'] != $invoice->customer()->get('c_id')) {
		$customer = company::load($form['customer']);
		$invoice->customer($customer);
	}

	if($form['billing'] != $invoice->billing()->get('c_id')) {
		$billing = Company::load($form['billing']);
		$invoice->billing($billing);

		// 22% VAT for Finnish customers
		if($billing->get('c_country') == 'Finland') {
			$invoice->set('in_vat_pros', 22);
		} else {
			$invoice->set('in_vat_pros', 0);
		}

		$dist = person::load($_SESSION['bundle_calculator']['PERSON']['P_ID']);
		// If distributor, set both
		if($dist->get('p_type') == 1 && $invoice->customer()->get('c_id') != $dist->get('c_id')) {
			$invoice->customer($billing);
		}
	}

	if($invoice->get('in_currency') != $form['currency']) {
		$invoice->set('in_currency', $form['currency']);
		$invoice->set('in_rate', intra_currency_rate($form['currency']));
	}

	$invoice->set('p_id', $form['contact']);

	$invoice->set('in_nr', $form['nr']);
	$invoice->set('in_invoice_date', intra_date_convert($form['invoice_date']));
	$invoice->set('in_cust_reference', $form['cust_reference']);
	$invoice->set('in_order_date', intra_date_convert($form['order_date']));
	$invoice->set('in_rem', $form['rem']);
	$invoice->set('payment', $form['payment']);

	$invoice->set('in_due_date', intra_date_convert($form['due_date']));
	$invoice->set('in_fee', $form['fee'] * $form['rate']);

	$ag = Agreement::load(array('se_c_id' => $invoice->customer()->get('c_id')));
	if($ag->count() && $ag->get('ag_status') == 0) {
		$invoice->set('ag_nr', $ag->get('ag_nr'));
	}

	invoice_sessionsave($invoice);

	if($_POST['preview']) {
		drupal_goto('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$invoice->get('id').'/invoice.pdf');
	} elseif($_POST['save']) {
		drupal_goto('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$invoice->get('id').'/commit');
	}
}

function theme_invoice_form_invoice($form) {

	$o   = array();
	$o[] = theme('invoice_header', $form['in_id']['#value']);

	// Stupid table hack. Seamonkey doesn't handle float positioning very well
	$o[] = '<table border="0" width="100%"><tr><td width="40%">';
	$o[] = theme_invoice_billing($form['billing']);
	$o[] = '</td><td>';
	$o[] = theme_invoice_info($form);
	$o[] = '</td></tr></table>';

	$o[] = theme_invoice_rem($form);

	$form['fee']['#printed'] = $form['due_date']['#printed'] = $form['currency']['#printed'] = true;

	//$o[] = theme_invoice_yalsum($form);

	$o[] = theme_invoice_products($form);

	$o[] = '<!-- Rest of stuff -->';

	$o[] = form_render($form);

	return implode("\n", $o);
}



function invoicefactory_download_invoice_pdf() {

	$in_id = arg(5);

	try {
		$invoice = invoice_sessionload($in_id);
		$pdf = intra_invoice_pdf($invoice);
	} catch(Exception $e) {
		if($in_id && isset($_SESSION['bundle_calculator']['invoices'][$in_id])) {
			invoicefactory_current_in_id($in_id);
		}

		$invoice = invoice_build();
/*
		if(isset($_SESSION['bundle_calculator']['invoices'][$in_id]['tmp_x_file'])
			&& file_exists($_SESSION['bundle_calculator']['invoices'][$in_id]['tmp_x_file'])
			&& strtotime($invoice->get('in_chgdate')) < filemtime($_SESSION['bundle_calculator']['invoices'][$in_id]['tmp_x_file'])) {
			// PDF created later, use it
			$pdf = $_SESSION['bundle_calculator']['invoices'][$in_id]['tmp_x_file'];
		} else {
			$pdf = intra_invoice_pdf($invoice);
			$_SESSION['bundle_calculator']['invoices'][$in_id]['tmp_x_file'] = $pdf;
		}
*/
	}



	if($pdf) {
		header('Content-disposition: inline; filename=invoice.pdf');
		header('Content-Type: application/pdf');
		echo file_get_contents($pdf);
	}
	die();
}

function theme_invoice_header($invoice) {

	$owner = variable_get('intra_owner_company_c_id', 157993261);
	$invoice = Invoice::load($invoice);

	if($invoice->billing()->get('c_id') != $_SESSION['bundle_calculator']['PERSON']['C_ID']) {
		$dist = Company::load($_SESSION['bundle_calculator']['PERSON']['C_ID']);

		if($dist->get('c_type') == 1)
			$company = $dist;
		else
			$company = Company::load($owner);
	} else {
		$company = Company::load($owner); // Us
	}

	$c_cname = $company->get('c_cname');

	$logo = $company->get('c_logo');

	if($logo) {
		$logo = theme('image', $logo, $c_cname, $c_cname, array('style' => 'float:left', 'border' => 0, 'class' => 'fn org'));

		if($c_url = $company->get('c_url')) {
			$logo = '<a href="'.$c_url.'" class="fn org url">'.$logo.'</a>';
		}
	} else {
		$logo = '<span class="fn org">'.check_plain($company->get('c_cname')).'</span>';
	}

	$vatid = '';
	if($vat = $company->get('c_vat')) {
		$vatid = theme('abbr', t('Vat ID'), t('European Union Value Added Tax')).
				': '.check_plain($vat);
	}

	$address = theme('intra_formataddress', $company,'span');

	return <<<EOL
	<div id="invoice-header" class="vcard">
		<div>
			{$logo}
			<div class="vat">{$vatid}</div>
		</div>
		<div style="clear:both;">{$address}</div>
	</div>
	<hr />
EOL;
}

function theme_invoice_billing(&$form) {
	$company = company::load($form['#value']);

	$rows = array();

	$rows[] = array(
		array('class' => 'block', 'data' => t('Company')),
		array(
			'title' => $company->get('c_cname'),
			'data' => form_render($form)
		)
	);

	$rows[] = array(
		array('class' => 'block', 'data' => t('Address')),
		theme('intra_formataddress', $company)
	);

	if($phone = $company->get('c_phone')) {
		$rows[] = array(
			array('class' => 'block', 'data' => t('Phone')),
			check_plain($phone)
		);
	}

	$fs  = '<div id="invoice-billing-content">'.theme('table', array(), $rows).'</div>';

	$o  = '';
	$o .= theme('fieldset', array(
		'#title' => t('Billing'),
		'#attributes' => array(
		 	'id' => 'invoice-billing',
			'class' => 'vcard'
		),
		'#value' => $fs	
	));
	return $o;
}

function theme_invoice_info(&$form) {
	$rows = array();
	$types = array(
		'textfield',
		'markup',
		'select'
	);

	foreach (element_children($form['invoice']) as $i) {
		if(!in_array($form['invoice'][$i]['#type'], $types)) continue;

		$title = $form['invoice'][$i]['#title'];
		unset($form['invoice'][$i]['#title']);
		$rows[] = array(
			array('class' => 'block', 'data' => $title, 'nowrap' => 'nowrap'),
			form_render($form['invoice'][$i])
		);
	}

	$rows[] = array(
		array('class' => 'block', 'data' => t('Customer')),
		form_render($form['customer'])
	);

	$o .= theme('fieldset', array(
		'#title' => t('Invoice'),
		'#attributes' => array(
		 	'id' => 'invoice-info',
			'style' => 'text-align:left;'
		),
		'#value' => theme('table', array(), $rows)
	));
	return $o;
}

function theme_invoice_totalsum(&$form) {

	$o   = array();
	$o[] = '<div class="invoice-notes">';
	$o[] = '<div class="total-price">';
	$m = $form['fee']['#value'];
	$o[] = theme('money', $m, $form['currency']['#value']);
	$o[] = '</div>';

	$form['fee']['#printed'] = $form['due_date']['#printed'] = $form['currency']['#printed'] = true;

	$o[] = '<div class="invoice-due">';
	$o[] = t('Due %date', array('%date' => theme('lcdate', $form['due_date']['#value'])));
	$o[] = '</div>';

	$o[] = '</div>';

	return implode("\n", $o);
}

function theme_invoice_products(&$form) {

	$headers = array(
		t('Nr'),
		t('Item'),
		t('Serials'),
		t('Rate'),
		array('data' => t('Disc. %'), 'align' => 'center', 'width' => '80'),
		array('data' => t('Subtotal'), 'align' => 'right', 'width' => '100')
	);

	$rows = array();

	$i = 0;

	$subtotal = 0;

	foreach(element_children($form['items']) as $l) {
		$i++;
		$rem = $serials = array();

		$jj = false;
		$childNodes = element_children($form['items'][$l]['rem']);
		$nChildNodes = count($childNodes);
		foreach($childNodes as $j) {
			if(!$jj && $nChildNodes > 1) {
				$jj = true;
				$rem[] = '<div class="invoice-item-header">'.form_render($form['items'][$l]['rem'][$j]).'</div>';
			} else {
				$rem[] = '<div class="invoice-item">'.form_render($form['items'][$l]['rem'][$j]).'</div>';
			}
		}

		foreach(element_children($form['items'][$l]['serials']) as $j) {
			$serials[] = '<div class="serial">'.form_render($form['items'][$l]['serials'][$j]).'</div>';
		}

		$form['items'][$l]['fee']['#printed'] = $form['items'][$l]['rate']['#printed'] = true;

		$form['items'][$l]['discount']['#value'] = number_format($form['items'][$l]['discount']['#value']);

		$rows[] = array(
			$i,
			implode("\n", $rem),
			array('class' => 'serials', 'data' => implode("\n", $serials)),
			theme('money', $form['items'][$l]['rate']['#value'], 'EUR'),
			array('align' => 'center', 'data' => form_render($form['items'][$l]['discount']).' %'),
			theme('money', $form['items'][$l]['fee']['#value'], 'EUR')
		);

		$subtotal += $form['items'][$l]['fee']['#value'];
	}

	$rows[] = array(
		array(
			'data' => t('Subtotal'),
			'colspan' => 5,
			'class' => 'totalrow block'
		),
		array(
			'data' => theme('money', $subtotal, 'EUR'),
			'class' => 'totalrow'
		)
	);

	$rows[] = array(
		array(
			'data' => '&nbsp;',
			'colspan' => 6
		)
	);

	foreach(element_children($form['additional']) as $l) {
		$rows[] = array(
			array(
				'data' => $form['additional'][$l]['#title'],
				'colspan' => 5,
				'class' => 'block'
			),
			theme('money', $form['additional'][$l]['#value'], 'EUR')
		);
		$form['additional'][$l]['#printed'] = true;
	}

	$rows[] = array(
		array(
			'data' => '&nbsp;',
			'colspan' => 6
		)
	);

	$total = $form['fee']['#value']+$form['additional']['vat']['#value'];

	$rows[] = array(
		array(
			'data' => t('Total'),
			'colspan' => 5,
			'class' => 'totalrow block'
		),
		array(
			'data' => theme('money', $total, 'EUR'),
			'class' => 'totalrow'
		)
	);

	return theme('table', $headers, $rows, array('width' => '100%', 'class' => 'product-listing'));
}

function theme_invoice_rem(&$form) {
	$o = '';

	if(module_exists('tinymce') && false) {
		$form['rem']['#resizable'] = false;

		$tinymce = drupal_get_path('module', 'tinymce');
		// TinyMCE Compressor
		if (file_exists($tinymce . '/tinymce/jscripts/tiny_mce/tiny_mce_gzip.php')) {
			drupal_add_js($tinymce . '/tinymce/jscripts/tiny_mce/tiny_mce_gzip.php');
		} else {
			drupal_add_js($tinymce . '/tinymce/jscripts/tiny_mce/tiny_mce.js');
		}

		$o .= theme('intra_tinymce');
	}

	$o .= form_render($form['rem']);

	return $o;
}

/**
 * Add tinymce settings to page - once.
 * Coped from tinymce module
 */
function theme_intra_tinymce() {
	static $set;
	if($set) return;
	$set = true;

	global $user;
	if (!$profile_name) {
		$profile_name = db_result(db_query('SELECT s.name FROM {tinymce_settings} s INNER JOIN {tinymce_role} r ON r.name = s.name WHERE r.rid IN (%s)', implode(',', array_keys($user->roles))));
	}
	$profile = tinymce_profile_load($profile_name);
	$init = tinymce_config($profile);
	$init['mode'] = 'none';

	foreach ($init as $k => $v) {
		$v = is_array($v) ? implode(',', $v) : $v;
		// Don't wrap the JS init in quotes for boolean values or functions.
		if (strtolower($v) != 'true' && strtolower($v) != 'false' && $v[0] != '{') {
			$v = '"'. $v. '"';
		}
		$settings[] = $k. ' : '. $v;
	}
	$tinymce_settings = implode(",\n    ", $settings);

	return <<<EOL
<script type="text/javascript">
tinyMCE.init({
	$tinymce_settings
});
</script>
EOL;
}

function theme_intra_formatname($person) {
	$name = array();
	if($title = $person->get('p_title')) $name[] = sprintf('<em class="honorific-prefix" style="display:none;">%s</em>', check_plain($title));
	if($fname = $person->get('p_fname')) $name[] = sprintf('<span class="given-name">%s</span>', check_plain($fname));
	if($lname = $person->get('p_lname')) $name[] = sprintf('<span class="family-name">%s</span>', check_plain($lname));

	return implode(' ', $name);
};

function theme_intra_formataddress($company, $separator='div') {
	$addr = array();

	if($street = $company->get('c_street')) {
		$addr[] = ' class="street-address">'.check_plain($street);
	} elseif($zip = $company->get('c_zip')) {
		$addr[] = ' class="post-office-box">'.check_plain($zip);
	}

	$addr[] = '><span class="postal-code">'.check_plain($company->get('c_zip')).'</span> <span class="locality">'.check_plain($company->get('c_city')).'</span>';

	$country = $company->get('c_country');
	if(module_exists('terraserial')) {
		$country = _terraserial_flagfy($country);
	} else {
		$country = check_plain($country);
	}
	$addr[] = ' class="country">'.$country;

	return '<address class="adr"><'.$separator.implode('</'.$separator.'> <'.$separator, $addr).'</'.$separator.'></address>';
}

