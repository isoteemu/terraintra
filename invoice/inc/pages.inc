<?php

function intra_invoice_page_infobox(Invoice $invoice) {
	$sections = module_invoke_all('intra_invoice_infobox', $invoice);
	echo theme('intra_invoice_infobox', $invoice, $sections);
}

function intra_invoice_intra_invoice_infobox(Invoice $invoice) {
	$r = array();

	$r['order_details']= array(
		'#title' => t('Order details'),
		'#value' => theme('intra_invoice_infobox_orderdetails', $invoice)
	);

	return $r;
}

function intra_invoice_page(Invoice $invoice) {

	return theme('intra_invoice_details', $invoice);

}

function intra_invoice_page_file(Invoice $invoice) {
	if($file = $invoice->get('x_file')) {
		drupal_goto('https://www.example.com/terraintra/files/'.urlencode($file));
	} else {
		drupal_not_found();
	}
}

/**
 * Company invoice listing
 */
function intra_invoice_page_company(Company $company) {

	if($prospect = $company->getProspectOf()) {
		Intra_CMS()->dfb($prospect);
		$distributor = $prospect->getCompany();
		if($distributor != intra_api_currentuser()->getCompany()) {
			drupal_set_message(t('!customer is a customer of !distributor, and one should check out their <a href="@distribtor-invoices-url">invoices</a>.', array(
				'!customer' => $company,
				'!distributor' => $distributor,
				'@distribtor-invoices-url' => url(intra_api_url($distributor).'/invoice')
			)));
		}
	}


	return theme('intra_invoice_listing', $company->invoices());
}

function template_preprocess_intra_invoice_details(&$variables) {
	$variables['view'] = intra_api_view($variables['invoice']);
}

function template_preprocess_intra_invoice_listing(&$variables) {
	if(module_exists('jquery_ui')) {
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/jquery-ui.css', 'module', 'screen');
		drupal_add_css(JQUERY_UI_PATH.'/themes/base/ui.selectable.css', 'module', 'screen');
		jquery_ui_add(array('ui.selectable'));
	}
}

function template_preprocess_intra_invoice_infobox(&$variables) {
	$variables['view'] = intra_api_view($variables['invoice']);
	drupal_add_css(drupal_get_path('module', 'intra_api').'/css/infobox.css', 'module');
}

function template_preprocess_intra_invoice_infobox_orderdetails(&$variables) {
	$variables['view'] = intra_api_view($variables['invoice']);
	drupal_add_css(drupal_get_path('module', 'intra_api').'/css/infobox.css', 'module');
}
