<?php
/**
 * @file
 *   Horde specified modifications
 */

class Intra_CMS_Horde extends Intra_CMS_Common implements Intra_CMS_Interface {
	protected $_memcache;

	public function init() {
		$this->overrides['memcache'] = array(&$this, 'memcache');
	}

	public static function detect() {
		if(defined('HORDE_BASE') && defined('HORDE_VERSION')) return HORDE_VERSION;
		elseif(defined('HORDE_BASE')) return '1.0';

		return false;
	}

	public function &memcache() {
		if(!$this->_memcache) {
			global $conf;
			if($conf['memcache']['enabled']) {
				include_once('Horde/Memcache.php');
				$this->_memcache =& Horde_Memcache::singleton()->_memcache;
			} else {
				$this->_memcache = new Intra_CMS_CallManager_Memcache;
			}
		}
		return $this->_memcache;
	}
}
