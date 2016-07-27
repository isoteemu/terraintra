<?php

abstract Intra_History_Provider {
	protected $providers = array();

	protected $reference;

	public function setReference(&$reference) {
		$this->reference =& $reference;
		return $this;
	}

	public function &getReference() {
		return $this->reference;
	}

	/**
	 * Return classes for which this provides history
	 */
	public function getProviders() {

	}

}


class Intra_History_Provider_Downloads extends Intra_History_Provider {

}

class Intra_History_Provider_Emails extends Intra_History_Provider {

}

class Intra_History_Provider_Registrations extends Intra_History_Provider {

}


class Intra_History {
	const providers_dir = './Providers';

	protected static $providers = array();

	protected $_reference;

	public function __construct(Intra_Object &$reference) {
		$this->setReference($reference);
	}

	public function setReference(Intra_Object &$reference) {
		$this->_reference =& $reference;
		return $this;
	}

	public function &getReference() {
		if(!isset($this->_reference))
			throw new Exception('Missing reference object');

		return $this->_reference;
	}

	public function getProviders() {
		$class = get_class($this->getReference());
		return $this->_getProviders($class);
	}

	public function _getProviders($type) {

	}

	public function setProvider($type, $providerClass) {
		if(!isset($this->providers[$type]))
			$this->providers[$type] = array();
	}

	public function getHistory() {
		
	}

	protected function scanProviders() {
		$providers = glob(self::providers_dir, '*.php');
		foreach($providers as $provider) {

		}
	}
}