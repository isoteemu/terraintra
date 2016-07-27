<?php

define('INTRA_PATH', 'modules/teemu/intra/');

define('INTRA_INVOICE_REM_PAYMENT', 405);
define('INTRA_INVOICE_REM_DEFAULT', 507);
define('INTRA_INVOICE_REM_MAINTENANCE', 508);

if(!function_exists('_filter_htmlcorrector')) {
	/**
	* Scan input and make sure that all HTML tags are properly closed and nested.
	*/
	function _filter_htmlcorrector($text) {
		// Prepare tag lists.
		static $no_nesting, $single_use;
		if (!isset($no_nesting)) {
			// Tags which cannot be nested but are typically left unclosed.
			$no_nesting = drupal_map_assoc(array('li', 'p'));
		
			// Single use tags in HTML4
			$single_use = drupal_map_assoc(array('base', 'meta', 'link', 'hr', 'br', 'param', 'img', 'area', 'input', 'col', 'frame'));
		}
		
		// Properly entify angles.
		$text = preg_replace('!<([^a-zA-Z/])!', '&lt;\1', $text);
		
		// Split tags from text.
		$split = preg_split('/<([^>]+?)>/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		// Note: PHP ensures the array consists of alternating delimiters and literals
		// and begins and ends with a literal (inserting $null as required).
		
		$tag = false; // Odd/even counter. Tag or no tag.
		$stack = array();
		$output = '';
		foreach ($split as $value) {
			// Process HTML tags.
			if ($tag) {
				list($tagname) = explode(' ', strtolower($value), 2);
				// Closing tag
				if ($tagname{0} == '/') {
					$tagname = substr($tagname, 1);
					// Discard XHTML closing tags for single use tags.
					if (!isset($single_use[$tagname])) {
						// See if we possibly have a matching opening tag on the stack.
						if (in_array($tagname, $stack)) {
							// Close other tags lingering first.
							do {
								$output .= '</' . $stack[0] . '>';
							} while (array_shift($stack) != $tagname);
						}
						// Otherwise, discard it.
					}
				}
				// Opening tag
				else {
					// See if we have an identical 'no nesting' tag already open and close it if found.
					if (count($stack) && ($stack[0] == $tagname) && isset($no_nesting[$stack[0]])) {
						$output .= '</' . array_shift($stack) . '>';
					}
					// Push non-single-use tags onto the stack
					if (!isset($single_use[$tagname])) {
						array_unshift($stack, $tagname);
					}
					// Add trailing slash to single-use tags as per X(HT)ML.
					else {
						$value = rtrim($value, ' /') . ' /';
					}
					$output .= '<' . $value . '>';
				}
			}
			else {
			// Passthrough all text.
				$output .= $value;
			}
			$tag = !$tag;
		}
		// Close remaining tags.
		while (count($stack) > 0) {
			$output .= '</' . array_shift($stack) . '>';
		}
		return $output;
	}
}

function intra_format_xml_elements($array) {
	$output = '';
	foreach ($array as $key => $value) {
		if (is_numeric($key)) {
			if ($value['key']) {
				$output .= ' <' . $value['key'];
				if (isset($value['attributes']) && is_array($value['attributes'])) {
					$output .= drupal_attributes($value['attributes']);
				}
		
				if (isset($value['value']) && $value['value'] != '') {
					$output .= '>' . (is_array($value['value']) ? intra_format_xml_elements($value['value']) : (($value['raw']) ? $value['value'] : check_plain($value['value']))) . '</' . $value['key'] . ">\n";
				} else {
					$output .= " />\n";
				}
			} else {
				$output .= " />\n";
			}
		}
		else {
			$output .= ' <' . $key . '>' . (is_array($value) ? intra_format_xml_elements($value) : check_plain($value)) . "</$key>\n";
		}
	}
	return $output;
}

function intra_xml_invoice($invoice) {
	$data = array(
		'key' => 'invoice',
		'value' => array(),
		'attributes' => array(
			'nr' => $invoice->get('in_nr'),
			'version' => '0.9.1'
		)
	);

	// Owner
	$owner = Company::load(array('c_type' => 0));
	$ownerxml = intra_xml_company($owner);
	$owneracc = $owner->people()->each()->filter(array('p_class' => 'Account'));

	if($owneracc->count()) {
		$owneraccxml = _intra_object_to_xml($owneracc);
	} else {
		$owneraccxml = array();
	}
	$owneraccxml = array('key' => 'person', 'value' => $owneraccxml );

	$delivery = intra_xml_company($invoice->customer());
	$billing = intra_xml_company($invoice->billing());
	$items = intra_xml_items($invoice);

	$rows = array();

	if($p_id = $invoice->get('p_id'))
		$accountant = Person::load($p_id);


	if(!$accountant)
		$accountant = $invoice->billing()->people()->each()->filter(array('p_class' => 'Account'));

	if($accountant->get('p_id')) {
		$person = array(
			'key' => 'person',
			'value' => _intra_object_to_xml($accountant)
		);
	} else {
		$person = array();
	}

	$data['value'] = array(
		array(
			'key' => 'owner',
			'value' => array(
				$ownerxml,
				$owneraccxml
			)
		),
		array(
			'key' => 'delivery',
			'value' => array(
				$delivery
			)
		),
		array(
			'key' => 'billing',
			'value' => array(
				$billing,
				$person
			)
		),
		array(
			'key' => 'items',
			'value' => $items
		)
	);

	// HACK - temporarily convert invoice to EUR
	if(($in_rate = $invoice->get('in_rate')) != 1) {
		$oldSum = $invoice->get('in_fee');
		$invoice->set('in_fee', $oldSum / $in_rate);
		$inXml = _intra_object_to_xml($invoice);
		$invoice->set('in_fee', $oldSum);
	} else {
		$inXml = _intra_object_to_xml($invoice);
	}


	$data['value'] = array_merge($data['value'], $inXml);

	$xml  = '<?xml version="1.0" encoding="utf-8"?>';
	$xml .= intra_format_xml_elements(array($data));

	return $xml;
}

function _intra_object_to_xml($object) {
	$vars = $object->attributes();

	$data = array();

	foreach($vars as $xmlKey => $realKey) {
		$val = $object->get($realKey);
		if($xmlKey == 'rem' || $xmlKey == 'payment') {
			if(preg_match('/<[^>]+>/i', $val)) {
				// HTML Markup. Clean.
				$val = _filter_htmlcorrector($val);
				$val = html_entity_decode($val, ENT_NOQUOTES,'UTF-8');
				$data[] = array(
					'key' => 'rem',
					'attributes' => array(
						'type' => $xmlKey,
					),
					'value' => rtrim($val),
					'raw' => true,
				);
			} else {
				$val = html_entity_decode($val, ENT_NOQUOTES,'UTF-8');
				$r = array();
				foreach(explode("\n", $val) as $line) {
					$r[] = array(
						'key' => 'line',
						'value' => rtrim($line)
					);
				}
				$data[] = array(
					'key' => 'rem',
					'attributes' => array(
						'type' => $xmlKey,
					),
					'value' => $r,
					'raw' => true,
				);
			}
			continue;

		} elseif(!is_scalar($val) ) {
			if(is_array($val)) {
				foreach($val as $e) {
					$data[] = array(
						'key' => $xmlKey,
						'value' => $e
					);
				}
			}

			continue;
		}
		if($val) {
			$data[] = array(
				'key' => $xmlKey,
				'value' => $val
			);
		}
	}
	return $data;
}

function intra_xml_company($company) {
	$data = array(
		'key' => 'company',
		'value' => _intra_object_to_xml($company),
		'attributes' => array(
			'id' => $company->get('id')
		)
	);
	return $data;
}

function intra_xml_items($invoice) {
	$pc = $invoice->prices();

	$data = array();

	$bundles = $pc->bundles();
	$separate = $pc->additional();

	foreach($bundles->getChildren() as $item) {
		$rem = array();
		$serials = array();

		$rem[] = array(
			'key' => 'line',
			'value' => $item->get('se_rem').':'
		);

		foreach($item->getChildren() as $sub) {
			$rem[] = array(
				'key' => 'line',
				'value' => $sub->get('pr_name')
			);

			$serials[] = array(
				'key' => 'serial',
				'value' => $sub->get('se_serial')
			);
		}

		$data[] = array(
			'key' => 'item',
			'value' => array(
				'rem' => $rem,
				'serial' => $serials,
				'rate' => $item->get('rate'),
				'discount' => $item->get('se_discount'),
				'fee' => $item->get('se_fee')
			)
		);

	}

	foreach($separate as $item) {
		$data[] = array(
			'key' => 'item',
			'value' => array(
				'rem' => $item->get('pr_name'),
				'serial' => $item->get('se_serial'),
				'rate' => $item->get('rate'),
				'discount' => $item->get('se_discount'),
				'fee' => $item->get('se_fee')
			)
		);
	}

	$additional = array(
		'key' => 'additional',
		'value' => array()
	);

	if($pc->periodLength() > 0 && $pc->maintenancePrice() > 0) {
		if($invoice->get('in_type') == Invoice::TYPE_MAINTENANCE) {
			$data[] = array(
				'key' => 'additional',
				'value' => array(
					'rem' => t('Maintenance fee from %from to %to', array('%from' => $pc->periodFrom(), '%to' => $pc->periodTo())),
					'fee' => $pc->maintenancePrice()
				)
			);
		} else {
			$data[] = array(
				'key' => 'additional',
				'value' => array(
					'rem' => t('Maintenance fee for new licenses from %from to %to', array('%from' => $pc->periodFrom(), '%to' => $pc->periodTo())),
					'fee' => $pc->maintenancePrice()
				)
			);
		}
	}

	if($invoice->billing()->get('c_type') == 1) {
		$percent = $invoice->billing()->get('c_discount');
		$disc = $pc->total() * ($percent/100);

		$data[] = array(
			'key' => 'additional',
			'value' => array(
				'rem' => t('Distributor discount %percent %', array('%percent' => $percent)),
				'fee' => $disc
			)
		);
	}

	return $data;
}

function invoice_build($in_id=null, $data=array()) {

	//unset($_SESSION['bundle_calculator']['invoices'][$in_id]);
	$in_id = ($in_id === null) ? invoicefactory_current_in_id() : $in_id;

	if($in_id && isset($_SESSION['bundle_calculator']['invoices'][$in_id])) {

		Intra_CMS()->dfb($in_id, 'Loading pending invoice');
		//$invoice = Invoice::factory('Invoice',$_SESSION['bundle_calculator']['invoices'][$in_id]);
		$invoice = invoice_sessionload($in_id);

		Intra_CMS()->dfb($invoice, "Loaded invoice");
	} else {

		$rem = '';
		if($node = _lataus20_node_load_t(INTRA_INVOICE_REM_DEFAULT)) {
			$rem = $node->body;
		}

		// Populate with default values.
		$data = array_merge(array(
			'in_type'			=> Invoice::TYPE_SALE,
			'in_nr'				=> INTRA_INVOICE_DRAFT,
			'in_rem'			=> $rem,
			'in_invoice_date'	=> date('c'),
			'in_due_date'		=> date('c', time()+2592000),
			'in_currency'		=> 'EUR',
			'in_chgby'			=> $_SESSION['bundle_calculator']['PERSON']['P_USER'],
			'in_chgdate'		=> date('c')
		), $data);

		$invoice = Invoice::factory('Invoice', $data);

		$customer = $invoice->customer($_SESSION['bundle_calculator']['customer']);

		$dist = Person::load($_SESSION['bundle_calculator']['PERSON']['P_ID']);
	
		if($_SESSION['bundle_calculator']['billing']) {
			$invoice->billing($_SESSION['bundle_calculator']['billing']);
		} elseif($dist->get('p_type') == 1) { // 1== Distributor
			$invoice->billing($_SESSION['bundle_calculator']['PERSON']['C_ID']);
		} else {
			$invoice->billing($_SESSION['bundle_calculator']['customer']);
		}
	}

	if(!$invoice->get('in_invoice_date')) {
		$invoice->set('in_invoice_date', date('c'));
	}
	if(!$invoice->get('in_due_date')) {
		// 30 days payment time
		$invoice->set('in_due_date', date('c', strtotime($invoice->get('in_invoice_date'))+2592000));
	}
	if(!$invoice->get('in_currency')) {
		$invoice->set('in_currency', 'EUR');
	}

	// 22% VAT for Finnish customers
	/// TODO
	if($invoice->billing()->get('c_country') == 'Finland') {
		$invoice->set('in_vat_pros', 22);
	} else {
		$invoice->set('in_vat_pros', 0);
	}

	if($invoice->billing()->get('c_type') == 1) {
		$percent = $invoice->billing()->get('c_discount');
		$price = $price * (1-$percent/100);
		$total -= $disc;

	}

	return $invoice;
}

function invoice_build_sale(&$invoice) {
	$invoice->set('in_type', Invoice::TYPE_SALE);

	$se_type = Intra_Product_Serial::workstation;
	if($invoice->customer()->isAcademic()) {
		$se_type = Intra_Product_Serial::temporary;
	}

	_invoice_build_addserials($invoice, $se_type);

}

function invoice_build_rent(&$invoice) {
	$invoice->set('in_type', Invoice::TYPE_RENT);

	$se_type = Intra_Product_Serial::workstation;
	_invoice_build_addserials($invoice, $se_type);
}

function _invoice_build_addserials(&$invoice, $se_type=Intra_Product_Serial::workstation) {

	$rownr = 0;
	foreach($_SESSION['bundle_calculator']['edit']['new'] as $product) {
		if($product['number'] <= 0) continue;

		$map = Product_Map::load(array('pr_name' => $product['product']));

		$i = 0;
		$pr_id = $map->get('pr_id');
		while($i < $product['number']) {
			$rownr++;
			$serialDefaults = array(
				'pr_id' => $pr_id,
				'se_type' => $se_type,
				'se_rownr' => $rownr,
				'se_chgby' => $_SESSION['bundle_calculator']['PERSON']['P_USER'],
			);
			$serial = Intra_Product::factory('Intra_Product', $serialDefaults);
			$invoice->addArticle($serial);
			$i++;
		}
	}

	$pc =& $invoice->prices();
	foreach($_SESSION['bundle_calculator']['edit']['old'] as $product) {
		$map = Product_Map::load(array('pr_name' => $product['product']));
		$pc->oldLicenses[$map->get('pr_id')] = $product['number'];
	}

	if($_SESSION['bundle_calculator']['edit']['maint']['from']) {
		$pc->periodFrom($_SESSION['bundle_calculator']['edit']['maint']['from']);
	}
	if($_SESSION['bundle_calculator']['edit']['maint']['to']) {
		$pc->periodTo($_SESSION['bundle_calculator']['edit']['maint']['to']);
	}

	if($pc->periodLength() > 0 && $invoice->articles()->count() >= 1) {
		$invoice->articles()->each()->set('se_agreement', 'X');
		$invoice->articles()->each()->set('se_type', Intra_Product_Serial::pool);
		$to = explode('/', $pc->periodTo());
		$time = mktime(0,0,0,$to[0]+1,-1,$to[1]);
		$invoice->articles()->each()->set('se_maint_date', date('c', $time));
	}

	// Update article prices
	$price = $pc->total();

	if($_SESSION['bundle_calculator']['currency'] != 'EUR') {
		$invoice->set('in_currency', $_SESSION['bundle_calculator']['currency']);
		$curRate = intra_currency_rate($_SESSION['bundle_calculator']['currency']);
		$invoice->set('in_rate', $curRate);
		$price = $price * $curRate;
	}

	$invoice->set('in_fee', $price);

	Intra_CMS()->dfb($invoice, "Sale invoice");
}

function invoice_build_maintenance(&$invoice) {


	$rem = _lataus20_node_load_t(INTRA_INVOICE_REM_MAINTENANCE);
	if($rem) {
		$invoice->set('in_rem', $rem->body);
	}

	$invoice->set('in_type', Invoice::TYPE_MAINTENANCE);

	// Agremeent -serial
	$ag = Agreement::load(array('se_c_id' => $invoice->customer()->get('c_id')))->current();

	$item = array(
		'pr_id' => Intra_Product_Agreement::PR_ID,
		'se_type' => Intra_Product_Agreement::SE_TYPE,
		'se_serial' => $ag->get('ag_nr'),
		'se_chgby' => $_SESSION['bundle_calculator']['PERSON']['P_USER'],
		'se_chgdate' => date('c'),
		'se_rownr' => 1
	);

	$product = Intra_Product::factory('Intra_Product', $item);
	$invoice->addArticle($product);

	// Price calculation

	$pc =& $invoice->prices();
	if($_SESSION['bundle_calculator']['edit']['maint']['from']) {
		$pc->periodFrom($_SESSION['bundle_calculator']['edit']['maint']['from']);
	}
	if($_SESSION['bundle_calculator']['edit']['maint']['to']) {
		$pc->periodTo($_SESSION['bundle_calculator']['edit']['maint']['to']);
	}

	$to = explode('/', $pc->periodTo());
	$ag_time = mktime(0,0,0,$to[0]+1,-1,$to[1]);

	$price = $pc->total();

	$invoice->articles()->each()->set('se_maint_date', date('c', $ag_time));
	$invoice->articles()->each()->set('se_fee', $price);

	if($_SESSION['bundle_calculator']['currency'] != 'EUR') {
		$invoice->set('in_currency', $_SESSION['bundle_calculator']['currency']);
		$curRate = intra_currency_rate($_SESSION['bundle_calculator']['currency']);
		$invoice->set('in_rate', $curRate);
		$price = $price * $curRate;
	}

	$invoice->set('in_fee', $price);

	$invoice->set('in_fee', $ag->get('ag_fee'));

	Intra_CMS()->dfb($invoice, "Maintenance invoice");
}

function intra_goto_invoice_edit($invoice) {
	$id = $invoice->get('id');
	invoice_sessionsave($invoice);
	drupal_goto('distributors/login/'.$_SESSION['bundle_calculator']['menuid'].'/pc/invoicefactory/'.$id);
}

function invoicefactory_current_in_id($id=null) {
	static $in_id = null;
	if($id !== null) $in_id = $id;
	return $in_id;
}

function invoice_sessionsave($invoice) {
	$id = $invoice->get('id');

	$_SESSION['bundle_calculator']['invoices'][$id] = serialize($invoice);
}

/**
 * Load invoice from session.
 * Piss in the ass - Invoice ID can / will upon sleep/wakeup cycle.
 */
function &invoice_sessionload(&$id) {
	$invoice = unserialize($_SESSION['bundle_calculator']['invoices'][$id]);

	$id = $invoice->get('id');
	invoicefactory_current_in_id($id);
	return $invoice;
}

function intra_invoice_pdf($invoice) {
	
	$xml = intra_xml_invoice($invoice);
	return intra_pdf($xml, 'invoice');
}

/**
 * Create PDF from XML dump
 */
function intra_pdf($xml, $template='invoice') {

	$fop = variable_get('intra_fop_bin', '/usr/local/fop/fop');

	$fopcfg = dirname(__file__).'/fop/config.xml';
	$xslt   = sprintf('%s/fop/%s-fop.xsl', dirname(__file__), $template);

	if(!file_exists($xslt)) {
		throw new Exception('XSLT Template file '.$template.' doesn\'t exists.');
	}

	switch($template) {
		case 'html' :
			$xml = str_replace(
				array(
					'&nbsp;',
					'&'
				), array(
					' ', // OBS, non-breakable space
					'&#38;'
				),
				$xml
			); 
			break;
		default :
			break;
	}

	$tmppdf = tempnam('/tmp', 'FOPTRG');
	$tmpxml = tempnam('/tmp', 'FOPSRC');

	$i18n   = intra_xls_translations($xslt);

	file_put_contents($tmpxml, $xml);

	$lang = i18n_get_lang();

	$cmd = $fop.' -c '.escapeshellarg($fopcfg).' -xml '.escapeshellarg($tmpxml). ' -xsl '.escapeshellarg($xslt).' -pdf '.escapeshellarg($tmppdf).' -param Lang '.$lang.' -param strings '.$i18n;

	Intra_CMS()->dfb($cmd, 'Fop Command');

	putenv('DISPLAY');
	$status = 0;
	exec($cmd.' 2>&1', $output, $status);
	unlink($tmpxml);
	unlink($i18n);

	if($status) {
		throw new Exception(implode("\n", $output));
		unlink($tmppdf);
		return false;
	}

	if(function_exists('pdf_set_info')) {
		$author =  sprintf('%s %s', $_SESSION['bundle_calculator']['PERSON']['P_FNAME'], $_SESSION['bundle_calculator']['PERSON']['P_LNAME']);
		$title = t('%company Invoice %number', array(
			'%company' => $_SESSION['bundle_calculator']['PERSON']['C_CNAME'],
			'%number' => $invoice->get('in_nr')
		));

		// set PDF properties
		$pdf = pdf_new();
		pdf_open_file($pdf, $tmppdf);

		pdf_set_info($pdf, 'Author', $author);
		pdf_set_info($pdf, 'Title', $title);
		pdf_set_info($pdf, 'Creator', t('Invoice Creator'));
		pdf_set_info($pdf, 'Subject', $title);

		pdf_save($pdf);
	}

	return $tmppdf;
}

/**
 * Create translations file for $file
 */
function intra_xls_translations($file) {
	$strings = xsl_file_gettext($file);

	Intra_CMS()->dfb($strings, 'Strings');

	$locales = locale_supported_languages();

	$xml = new SimpleXMLElement('<strings />');

	foreach(array_keys($locales['name']) as $lang) {
		foreach($strings as $t) {
			$result = db_query("SELECT s.lid, t.translation FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE s.source = '%s' AND t.locale = '%s'", $t, $lang);
			// Translation found
			if ($trans = db_fetch_object($result)) {
				if (!empty($trans->translation)) {
					$trans = $xml->addChild('str', $trans->translation);
					$trans['xml:lang'] = $lang;
					$trans->addAttribute('name', $t);
				}
			} else {
				locale($t);
			}
		}
	}

	$trans = tempnam('/tmp', 'FOPI18N');
	file_put_contents($trans, $xml->asXml());

	return $trans;

}

/**
 * Retrieve used strings in xsl file
 */
function xsl_file_gettext($file, &$_seen = array()) {

	if(isset($_seen[$file])) return array();

	$_seen[$file] = $file;
	$r = array();

	$dom = simplexml_load_file($file);

	foreach($dom->xpath('//xsl:call-template[@name="getText"]/xsl:with-param[@name]') as $t) {
		$str = trim((string) $t['select'],"'");
		$r[] = $str;
	}

	foreach($dom->xpath('//xsl:include[@href]') as $include) {
		$href = (string) $include['href'];
		if(substr($href,0,2) == './') {
			$base = dirname(end($_seen));
			$href = $base.DIRECTORY_SEPARATOR.substr($href,2);
		}
		$r = array_merge($r, xsl_file_gettext($href, $_seen));
	}
	return array_unique($r);
}

/**
 * Select field for customer selection
 */
function intra_form_field_customer_select() {
	static $customers = array();
	if(!count($customers)) {
		theme('intra_add_js', 'listview.js');
		theme('intra_add_js', 'company-select.js');
		theme_add_style(drupal_get_path('module', 'bundle_calculator') . '/listview.css');

		$company = company::load($_SESSION['bundle_calculator']['PERSON']['C_ID']);

		$c = $company->customerList(array('c_cname'));

		$customers = array();
		foreach($c as $entry) {
			$f = drupal_strtoupper($entry['c_cname'][0]);
			$customers[$f][$entry['c_id']] = $entry['c_cname'];
		}

		ksort($customers,SORT_LOCALE_STRING);
		foreach(array_keys($customers) as $key) {
			asort($customers[$key],SORT_LOCALE_STRING);
		}
	}

	$form = array(
		'#type' => 'select',
		'#options' => $customers,
		'#default_value' => $_SESSION['bundle_calculator']['customer'],
		'#attributes' => array(
			'class' => 'company-select',
			'title' => t('Select customer'),
			'onchange' => 'this.form.submit();'
		),
	);

	return $form;
}

function intra_date_convert($date) {
	if(!$date) return '';
	if(preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4}|\d{2})$/', $date, $match)) {
		$y = $match[3];
		if(strlen($y) == 2) {
			$y = "20$y";
		}
		$time =  mktime(0,0,0,$match[2],$match[1],$y);
	} else {
		$time = strtotime($date);
	}

	return date('c', $time);
}

/**
 * Convert intra currency to currency_api currency
 */
function intra_currency_from_intra($currency) {
	switch($currency) {
		case '$' :
		case 'USD' :
		case 'US$' :
		case 'GEO' :
			return 'USD';
		case 'CA$' :
			return 'CAD';
		case '€' :
		case '' :
			return 'EUR';
		default:
			if(currency_api_get_desc($currency)) {
				return $currency;
			} else {
				teemu_dmesg('No currency %s found. Using EUR', $currency);
				return 'EUR';
			}
	}
}

function intra_currency($newCurrency=null) {
	static $currency;
	if(isset($newCurrency)) {
		$currency = $newCurrency;
	} elseif(!isset($currency)) {
		$currency = ($_SESSION['bundle_calculator']['currency']) ? $_SESSION['bundle_calculator']['currency'] : 'EUR';
	}

	return $currency;
}


function intra_currency_rate(&$currency, $rate=null) {
	static $rates = array(
		'EUR' => 1,
	);

	if($rate!==null) {
		if(!is_numeric($rate)) {
			throw new Exception('Given rate is not numeric.');
		} else {
			$rates[$currency] = $rate;
		}
	}

	if(!isset($rates[$currency])) {
		$info = currency_api_convert('EUR', $currency, 1);
		if($info['status'] == 1) {
			$rates[$currency] = $info['value'];
		} else {
			throw new Exception('Currency conversion failed :/. Message: '.$info['message']);
			$currency = 'EUR';
			return 1;
		}
	}

	return $rates[$currency];
}

/**
 * @param $currency In which currency money currently is
 */
function theme_money($money, $currency='EUR', $convert=true) {

	// Convert intra currencies
	$currency = intra_currency_from_intra($currency);

	$attributes = array(
		'class' => 'money',
		'title' => $money
	);

	if($convert) {
		$c = intra_currency();
		if(strtolower($c) != strtolower($currency)) {
			// Convert to EUR
			$from = ($currency == 'EUR') ? '1' : intra_currency_rate($currency);
			$to = ($c == 'EUR') ? '1' : intra_currency_rate($c);

			$rate = $to / $from;
			$converted = $money * $rate;

			$attributes['title'] = t('Using reference rates for %value %cur at rate %rate', array('%value' => money_format('%!.0i', $money ), '%cur' => $currency, '%rate' => $rate));
			$currency = $c;
			$money = $converted;
		}
	}

	$symbols = currency_api_get_symbols();
	$f = money_format('%!.0i', $money );

	switch($currency) {
		case 'CAD' : // total
		case 'GEO' : // douche
		case 'USD' : // bags
			$lf = $symbols[$currency].$f;
			break;
		default :
			$lf = $f.' '.$symbols[$currency];
			break;
	}

	return '<abbr '.drupal_attributes($attributes).'>'.$lf.'</abbr>';
}


function theme_intra_add_js( $js ) {
	$path = INTRA_PATH.'js/'.$js;
	drupal_add_js(INTRA_PATH.'js/'.$js.'?'.filemtime($path));
}
