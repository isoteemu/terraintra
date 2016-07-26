<?php

/**
 * Backwards compatibility functions
 */

/**
 * Convert old intra currency (eg. CA$) to ISO format
 */
function intra_api_retard_currency($intra_currency) {
	switch($intra_currency) {
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
			if(module_exists('currency_api')) {
				if(currency_api_get_desc($intra_currency)) {
					return $intra_currency;
				} else {
					watchdog('intra_api', t('No currency %currency found. Using EUR.', array('%currency' => $intra_currency)), NULL, WATCHDOG_WARNING);
					return 'EUR';
				}
			}
			break;
	}
	return $intra_currency;
}
