<?php
/**
 * @file
 *   Memcache.
 *   Memcache functions can be called as $Intra_CMS->memcache()->get().
 *   If memcache is not available, calls are replaced with stub functions,
 *   which in turns returns what memcache would return, if no entry is available.
 *
 * @see http://www.php.net/manual/en/book.memcache.php
 */

class Intra_CMS_CallManager_Memcache extends Intra_CMS_CallManager_Instance {

	protected $memcache;

	/**
	 * Init memcache.
	 * @todo Server parsing/adding
	 */
	public function init() {
		if(class_exists('memcache')) {
			$this->memcache = new Memcache;
			// HACK
			//$this->memcache->pconnect('localhost');
		} else {
			Intra_CMS()->dfb('Memcache extension is not available');

			if(!function_exists('memcache_debug')) {
				function memcache_debug($on_off) {}
			}

		}
	}

	/**
	 * Trigger Memcache call.
	 * @return Object
	 *   If connection to memcache is available, returns memcache object.
	 *   If connection is not available, returns self.
	 */
	public function call($args=null) {
		if($this->memcache) {
			return $this->memcache;
		}
		return $this;
	}

	/**
	 * Memcache stub functions, if real memcache is not available
	 * @{
	 */

	/**
	 * @param $host String
	 * @param $port Int
	 * @param $persistent Bool
	 * @param $weight Int
	 * @param $timeout Int
	 * @param $retry_interval Int
	 * @param $status Bool
	 * @param $failure_callback callback
	 * @param $timeoutms Int
	 * @return Bool
	 */
	public function addServer( $host, $port=11211, $persistent=true, $weight=null, $timeout=1, $retry_interval=-1, $status=true, $failure_callback=null, $timeoutms = null) {
		return false;
	}

	/**
	 * @param $key String
	 * @param $var Mixed
	 * @param $flag Int
	 * @param $expire Int
	 */
	public function add($key, $var, $flag = 0, $expire = null) {
		return false;
	}

	/**
	 * @param $key String
	 * @param $var Mixed
	 * @param $flag Int
	 * @param $expire Int
	 */
	public function set($key, $var, $flag = 0, $expire = null) {
		return false;
	}

	/**
	 * @param $key String
	 * @param $key Array
	 */
	public function get($key) {
		return false;
	}

	/**
	 * @param $key String
	 * @param $timeout Int
	 */
	public function delete($key, $timeout=0) {
		return false;
	}

	/**
	 * @} End of Memcache Stub functions
	 */
	
}