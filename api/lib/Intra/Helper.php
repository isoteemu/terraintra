<?php
/**
 * Intra_Helper object for simple item containers.
 * Provides some helpful functions for objects to extend.
 */

/**
 * @defgroup Database Database
 * Database is handled by Intra_Object. It uses Pear/MDB2 internally for database
 * connections.
 * 
 * Database layer can be directly accessing static Intra_Helper::db() function. When it's
 * first time called, it triggers new transaction. This causes that the database is not written
 * until Intra_Helper::dbCommit() is called.
 *
 * @see http://pear.php.net/package/MDB2
 */
class Intra_Helper implements Countable, Iterator {

	/**
	 * Children items -container.
	 */
	protected $children = array();

	/**
	 * Static database pointter.
	 *
	 * @ingroup Database
	 */
	private static $db;

	/**
	 * Debug toggle switch.
	 * @see Intra_Helper::debug()
	 */
	public static $strip = true;

	/**
	 * Add record into children.
	 * @see Intra_Helper::$children
	 */
	public function addChildren($obj,$id=null) {
		if($id === null) {
			if($obj instanceOf Intra_Object) {
				$this->children[$obj->get('id')] =& $obj;
			} else {
				$this->children[] =& $obj;
			}
		} else
			$this->children[$id] =& $obj;
	}

	/**
	 * Check if children collection contains item
	 * @param $id
	 *   ID of item to look for
	 * @return Boolean
	 */
	public function hasChildren($id) {
		return (array_key_exists($id, $this->children)) ? true : false;
	}

	public function &getChildren($id=null) {
		if($id===null) {
			return $this->children;
		} elseif($this->hasChildren($id)) {
			return $this->children[$id];
		} else {
			throw new UnexpectedValueException('No such children: '.$id);
			return $this;
		}
	}

	/**
	 * Remove/unset children.
	 * @param $id String
	 *   Key to remove
	 */
	public function removeChildren($id) {
		unset($this->children[$id]);
	}

	/**
	 * Merge own children to another.
	 */
	public function &mergeChildrenTo(Intra_Helper &$mergeTo, $by='id') {
		foreach($this->getChildren() as $child) {
			$key = $child->get($by);
			if(!is_scalar($key)) {
				throw new Exception('Returned key by ID "'.$by.'" is not scalar - cat be used to merge children');
				return $this;
			}
			if($mergeTo->hasChildren($key)) continue;
			$mergeTo->addChildren($child, $key);
		}
		return $this;
	}

	public function &mergeChildren(Intra_Helper $childrens) {
		$childrens->mergeTo($this);
		return $this;
	}

	/**
	 * Each object interface wrapper.
	 * This has been deprecated pretty much by implementing traversable.
	 */
	public function &each() {
		$e = new Intra_Helper_Each($this->children);
		return $e;
	}

	/**
	 * Sort children.
	 * Please note that SORT_LOCALE_STRING is not supported.
	 * @see sort()
	 */
	public function &sortChildren($field, $sort_order=SORT_ASC, $sort_flags=SORT_REGULAR) {

		if(count($this->children)) {
			$sorter = new Intra_Helper_Sort($field, $sort_order, $sort_flags);

			uasort($this->children, array($sorter, 'sort'));
		}
		return $this;
	}

	/**
	 * @name Countable Intrerface
	 * Functions which implements Countable -interface.
	 * @{
	 */
	/**
	 * Return number of children.
	 * Implements countable interface.
	 */
	public function count() {
		return count($this->children);
	}

	/**
	 * @}
	 */
	/**
	 * @name Iterator Interface
	 * Iterator interface functions.
	 * Provides methods to acces object as array.
	 * @see http://www.php.net/manual/en/class.iterator.php
	 * @ingroup Iterator
     * @{
	 */

	/**
	 * Return the current element.
	 * @see http://www.php.net/manual/en/iterator.current.php
	 */
	public function &current() {
		if(!$this->valid())
			reset($this->children);

		return current($this->children);
	}

	/**
	 * Return the key of the current element.
	 * @see http://www.php.net/manual/en/iterator.key.php
	 */
	public function key() {
		return key($this->children);
	}

	/**
	 * Move forward to next element.
	 * @see http://www.php.net/manual/en/iterator.next.php
	 */
	public function next() {
		return next($this->children);
	}

	/**
	 * Rewind the Iterator to the first element.
	 * @see http://www.php.net/manual/en/iterator.rewind.php
	 */
	public function rewind() {
		return reset($this->children);
	}

	/**
	 * Checks if current position is valid.
	 * @see http://www.php.net/manual/en/iterator.valid.php
	 */
	public function valid() {
		return key($this->children) !== null;
	}

	/**
	 * @}
	 */

	/**
	 * Internal debug function.
	 * By setting Intra_Helper::$strip to \c false, debug can be enabled
	 * for Intra_Helper -objects.
	 */
	public function debug($e) {
		if(self::$strip) return true;

		if(is_string($e)) {
			$args = func_get_args();
			if(count($args) >= 2) {
				array_shift($args);
				$e = vsprintf($e, $args);
			}
		}
		Intra_CMS()->dfb($e);
	}

	/**
	 * Magic. If no such method exist in current object, call
	 * on first child object.
	 */
	protected function __call($function, $args) {
		if(!method_exists($this,$function) && count($this->children)) {
			$key = key($this->children);
			return call_user_func_array(array(&$this->children[$key],$function), $args);
		} else {
			throw new Exception('No such callback "'.$function.'" function');
		}
	}

	/**
	 * @name Database
	 * Database functions.
	 * @see Database
	 * @{
	 */

	/**
	 * @deprecated Moved into Intra_Object
	 * @see Intra_Object::rewriteSql()
	 */
	protected function rewriteSql( $sql ) {
		return $sql;
	}

	/**
	 * Static handler for Database connection.
	 * @ingroup Database
	 * @throws RuntimeException
	 *   Failure when connection database.
	 * @param $dns (Optional) String
	 *   Database connection string. see:
	 *   http://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php
	 *   If is not set, tries using variable_get('terra_intra_db').
	 * @return
	 *   Database connection object.
	 */
	public static function &db($dns=null) {
		if(!isset(self::$db)) {
			include_once('MDB2.php');

			if($dns == null && function_exists('variable_get')) {
				$dns = variable_get('terra_intra_db', 'mysqli://localhost/terraintra-dev');
			}
			self::$db =& MDB2::Connect($dns, array(
				'debug'  => 2
			));

			if(PEAR::IsError(self::$db)) {
				throw new RuntimeException('Error in database connection: '.self::$db->getMessage());
			}

			// Let mysql handle charsets
			self::$db->query("SET NAMES 'utf8'");

			// Begin transaction
			if(!self::$db->inTransaction()) {
				self::$db->beginTransaction();
				//register_shutdown_function(array('Intra_Helper','dbRollback'));
			}
		}
		return self::$db;
	}

	/**
	 * Commit main transaction.
	 * @ingroup Database
	 */
	public static function dbCommit() {
		if(self::db()->inTransaction()) {
			self::db()->commit();
			self::debug('Commited data in database');
		}
	}

	/**
	 * Rollback main transaction.
	 * @ingroup Database
	 */
	public static function dbRollback() {
		if(self::db()->inTransaction()) {
			self::db()->rollback();
			self::debug('Rollbacked database');
		}
	}
	/**
	 * @}
	 */
}