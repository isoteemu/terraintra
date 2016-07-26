<?php
/**
 * @file
 * Class for checking VATID's.
 * @see http://ec.europa.eu/taxation_customs/vies/
 */

/**
 * VatID validation class.
 * Class uses soap class from pear to call into EC VatID registry,
 * and retrieves companys registered info.
 *
 * Example:
 * @code
 * try {
 *   $check = new Vatid('FI0748785');
 *   if($check->isValid()) {
 *     $address = intra_company_parseaddress($check->result['data']);
 *     [...]
 *   } else {
 *     // vatid is not registered
 *     drupal_set_message();
 *     [...]
 *   }
 * } catch(UnexpectedValueException) {
 *   // VatID incorrectly formated.
 *   drupal_set_message([...], 'error');
 * } catch(Exception $e) {
 *   // Failed to query EC -database
 *   [...]
 * }
 * @endcode
 *
 * @todo Move into PHP's native SoapClient -class.
 */
class Vatid {

	/**
	 * Instance of Soap_Client
	 */
	private $soap;

	/**
	 * Validation result.
	 */
	public $result = array(
		'valid' => false,
		'data' => null
	);

	/**
	 * Vatid constructor.
	 * @param $vatid
	 *   (Optional) vatid to check.
	 */
	public function __construct($vatid=null) {
		if($vatid) {
			$this->check($vatid);
		}
	}

	/**
	 * Check vatid from EC registry.
	 * @throws Exception
	 *   Failed to query EC registry. Doesn't mean that vatid is not valid.
	 * @throws UnexpectedValueException
	 *   vatid is not formated correctly.
	 * @see Vatid::parseVatid()
	 * @return Mixed
	 *   False if vatid is invalid. Array with registered data if validation successfull.
	 */
	public function check($vatid) {
		$vat = $this->parseVatid($vatid);
		if(!$this->soap) {

			if (!class_exists('SOAP_Client') && ! @include_once('SOAP/Client.php')) {
				throw new Exception('Could not include SOAP client library');
				return false;
			}
			$this->soap = new SOAP_Client('http://ec.europa.eu/taxation_customs/vies/api/checkVatPort?wsdl', true, false, array());
		}

		$this->result = $this->soap->call('checkVat', $vat);

		if (is_a($this->result, 'SOAP_Fault')) {
			$error = $this->result->getMessage();
			switch (true) {
				case strpos($error, 'INVALID_INPUT'):
					throw new Exception('The provided country code is invalid.');
					break;
				case strpos($error, 'SERVICE_UNAVAILABLE'):
					throw new Exception('The service is currently not available. Try again later.');
					break;
				case strpos($error, 'MS_UNAVAILABLE'):
					throw new Exception('The member state service is currently not available. Try again later or with a different member state.');
					break;
				case strpos($error, 'TIMEOUT'):
					throw new Exception('The member state service could not be reached in time. Try again later or with a different member state.');
					break;
				case strpos($error, 'SERVER_BUSY'):
					throw new Exception('The service is currently too busy. Try again later.');
					break;
			}
			return false;
		}
		return $result;
	}

	/**
	 * vatid format validation.
	 * Checks if vatid is in valid format, and parses it.
	 * @throws UnexpectedValueException.
	 *   Vatid is not formated correctly. 
	 * @return Mixed
	 *   Bool False if vatid is not correctly formated, array with contryCode and vatNumber.
	 */
	static function parseVatid($vatid) {
		if(!preg_match('/^([A-Z]{2})([0-9A-Za-z\+\*\.]{2,12})$/', $vatid, $match)) {
			throw new UnexpectedValueException('Invalid VATID format');
			return false;
		}
		return array(
			'countryCode' => $match[1],	/// VatID Countrycode.
			'vatNumber' => $match[2]	/// VatID Number.
		);
	}

	/**
	 * Was validated vatid valid.
	 * @see $this->vatid
	 * @return bool
	 */
	public function isValid() {
		if(is_a($this->result, 'SOAP_Fault')) return false;
		return ($this->result['valid']) ? true : false;
	}
}
