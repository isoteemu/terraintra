<?php

class vatid {

	private $soap;

	public $result = array(
		'valid' => false
	);

	public function __construct($vatid=null) {
		if($vatid) {
			$this->check($vatid);
		}
	}

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

	static function parseVatid($vatid) {
		if(!preg_match('/^([A-Z]{2})([0-9A-Za-z\+\*\.]{2,12})$/', $vatid, $match)) {
			throw new Exception('Invalid VATID format');
			return false;
		}
		return array(
			'countryCode' => $match[1],
			'vatNumber' => $match[2]
		);
	}

	public function isValid() {
		return ($this->result['valid']) ? true : false;
	}
}

$vat = new vatid('RO12540535');
print_r($vat->result);
