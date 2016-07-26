<?php

/**
 * @file
 *   Base class Intra_Object for handling database layer to ORM.
 * @see TerraIntra_Object
 */
/**
 * @defgroup TerraIntra_Object TerraIntra ORM
 * Provides wrappers for working with TerraIntra database tables / columns.
 * 
 * ORM-Objects are derived from class Intra_Object, which is responsible for loading and
 * saving database objects.
 *
 * Properties can be access for loaded objects directly from properties:
 * @code
 *   $fee = Invoice::load('12345')->in_fee;
 *   // AND
 *   $invoice = Invoice::load('12345');
 *   $invoice->in_fee = '3456.2';
 * @endcode
 * This allows access to "raw" database values. But generally more appropriate way is to use
 * Intra_Datastructure -provided Intra_Datastructure::get() and Intra_Datastructure::set():
 * @code
 *   $fee = Invoice::load('12345')->get('in_fee');
 *   // AND
 *   $invoice = Invoice::load('12345');
 *   $invoice->set('in_fee', '3456.2');
 * @endcode
 * This has advantage that the properties can normalize from/to database by implementing
 * $Intra_Datastructure->_accessors and $Intra_Datastructure->_mutators.
 *
 * To delete objects, Intra_Object::save() is used. But before calling it, visiblity should be set to
 * Intra_Object::DELETED:
 * @code
 *   $object->set('visible', Intra_Object::DELETED);
 *   $object->save();
 * @endcode
 * 
 * @see Intra_Object::dbCommit()
 * @see Intra_Object
 * @see Intra_Datastructure
 * @see DatabaseSchema
 */

/**
 * @page TerraIntra_Object_Loading TerraIntra Object loading
 * @ingroup TerraIntra_Object
 *
 * Object loading happens in Intra_Object::load($params, $class='Intra_Object'), where workflow is as follows:
 * \li First is created throwaway class, which is controlled by Intra_Object::factory(), and giving it id 0.
 *   Throwaway class creation is required so we can read object container.
 * \li If given $params is numeric, object loading is tested by Intra_Object::mayLoadObject($params)
 *   If we get something out of it, we're really happy and return it, as it means heavy lifting is done before.
 * \li If given $parmas is array, we need to convert it into Intra_Filter ruleset. This happens by using
 *   Intra_Object::createFilter($params).
 * \li Next SQL is created. Field names are retrieved by Intra_Object::tableSchema() (using throwaway class), and
 *   some special types - like binary point type - is specially handled. And if suitable, visibility - $Intra_Object->visible -
 *   check is appended.
 * \li Intra_Filter ruleset is converted into string (Intra_Filter->__toString()) and appended into SQL clause.
 * \li Already loaded objects in same container are added to be skipped for SQL lookup.
 * \li Intra_Object->rewriteSql() is applied for SQL. Notable Intra_Object::rewriteSql() modifies table names to match ones
 *   in $Intra_Object->dbTable.
 * \li SQL is executed (using Intra_Helper::db()->query()). If failure is detected, RuntimeException is thrown.
 * \li Intra_Object::mayLoadObject() is used against returned database rows, and if nothing is returned, new object is created
 *   by using Intra_Object::factory(), and added into suitable Intra_Object::$container by Intra_Object::factory().
 * \li Last step is to apply filter stack against previously loaded objects, and return matching objects.
 * 
 * Example, to load specified Company object, one can use:
 * @code
 *   $company = Company::load('12345');
 * @endcode
 * And Company::load() extends Intra_Object, but replaces default class by self. This is a bit stupid, and lazy static binding
 * should be used on php 5.3 onwards. But anyhow, previous example is equilavement of:
 * @code
 *   $company = Intra_Object::load('12345', 'Company');
 * @endcode
 * 
 * More complex loading params can be defined in array-format:
 * @code
 *   // Load TerraIntra admin company
 *   $admin_company = Company::load(array('c_type' => Company::TYPE_ADMIN));
 *   // Load everything else but admin
 *   $clients =  = Company::load(array('!c_type' => Company::TYPE_ADMIN));
 * @endcode
 * Note that when Intra_Object::load($params) is in array-format, keys first character can be a "magical", and used to specify
 * comparision function. More of that can be found Intra_Datastructure::_createFilter() which is responsible for converting arrays
 * into Intra_Filter -rules.
 * 
 * Notable thing is usage of Intra_Object::mayLoadObject(). This causes that objects which are already loaded once, are returned as
 * references from Intra_Object::$container, and ergo modifying property changes it everywhere where same object is used. Example:
 * @code
 *   $company = Company::load('12345');
 *   // Outputs "Company, Inc";
 *   echo $company->c_name;
 *   $company->set('c_cname', 'My changed company');
 * 
 *   // [ Later on code ... ]
 *
 *   // outputs "My changed company"
 *   echo Company::load('12345')->get('c_cname'); 
 * @endcode
 * @see Intra_Object::mayLoadObject()
 * @see Intra_Object::load()
 */

/**
 * @page TerraIntra_Object_Saving TerraIntra Object Saving
 * @ingroup TerraIntra_Object
 * Saving and deleting happens by calling $Intra_Object->save(). This triggers next behaviours:
 * \li Sub-Transaction is began.
 * \li Object visibility is checked by looking into $Intra_Object->visibility. If visibility is Intra_Object::DELETED, object is deleted:
 * \li \li Self is deleted from database by Intra_Object::deleteObject(), which creates suitable SQL and executes it.
 * \li \li And possibly related objects are deleted by by Intra_Object::deleteRelated().
 * \li If object is not to be deleted, it is to be saved:
 * \li \li Self is saved by Intra_Object::saveObject(), which handles actual database save. It also handles memcache cleanup.
 * \li \li Possibly related objects are deleted by Intra_Object::saveRelated().
 * \li Sub-Transaction is committed - or rollbacked if save/delete failed.
 *
 * Now, one would expect object to be saven. But its not so. This is so beacouse of "main" transaction. This transaction is triggered when
 * Intra_Object::db() is first time called. And to complete transaction, Intra_Object::dbCommit() should be called. Or if Intra_Object::save() failed,
 * possibly Intra_Object::dbRollback().
 *
 * @see Intra_Object::save()
 * @see Intra_Object::$visible
 * @see Intra_Object::delete()
 */

/**
 * @page TerraIntra_Object_Deletion TerraIntra Object Deletion
 * @ingroup TerraIntra_Object
 * @see Intra_Object::delete()
 * @see Intra_Object::$visible
 * @see Intra_Object::DELETED
 */

/**
 * @page TerraIntra_Object_examples TerraIntra ORM examples
 * @ingroup TerraIntra_Object
 * @{
 * Returns intance of Company object, where primary key is @c 157993261 :
 * @code
 *   Intra_Object::load(157993261, 'Company');
 * @endcode
 * But to ease things, Company should implement load function:
 * @code
 *   class Company extends Intra_Object {
 *     // Notice changed default for $class
 *     public function load($params=array(), $class='Company') {
 *       return parent::load($params, $class);
 *     }
 *   }
 *   // [...]
 *   Company::load(157993261);
 * @endcode
 * 
 * To save object, subclasses aren't required to extend any functions. But if class has children, they should be saved by extending
 * Intra_Object::saveRelated():
 * @code
 *   class ExampleORM extends Intra_Object {
 *     // [...]
 *     protected function saveRelated() {
 *       $this->children()->each()->saveObject();
 *       // And maybe:
 *       $this->children()->each()->saveRelated();
 *     }
 *   }
 * @endcode
 * 
 * @}
 */
/**
 * @name Database Schema
 * @defgroup DatabaseSchema Database Schema
 */

/**
 * Base ORM class for Intra Database Objects.
 * This is responsible for loading and saving database objects.
 * @ingroup TerraIntra_Object
 */
class Intra_Object extends Intra_Datastructure {

	/**
	 * @name Visiblity Flags
	 * Hidden objects aren't loaded by default, as Intra_Object::load()
	 * adds filter whichs prevents them from loading.
	 * @see Intra_Object::$visible
	 * @{
	 */
	/**
	 * Flag for defining object as deleted.
	 */
	const DELETED	= -1;
	/**
	 * Flag for defining object as hidden.
	 */
	const HIDDEN	= 0;
	/**
	 * Flag for normal visibility objects - ie visible.
	 */
	const VISIBLE	= 1;
	/**
	 * @}
	 */

	/**
	 * Static container.
	 * It's mapped on construct to $this->childer, so all
	 * same-class objects share same children
	 */
	private static $objects = array();

	/**
	 * Container for pointing modified objects.
	 */
	private static $modified = array();

	/**
	 * Container for Intra_Object::$objects.
	 * If is not defined, is detected and set in Intra_Object::factory().
	 * Usually it is class name, but in some cases - like Intra_Product_Serial and like -
	 * can be defined. Example Intra_Product_Agreement:
	 * @code
	 *   Intra_Product_Agreement extends Intra_Product {
	 *     / ... /
	 *     public $container	= 'Intra_Product';
	 *   }
	 * @endcode
	 */
	public $container;

	/**
	 * Database table(s).
	 * Defines database table/tables whichs Object works against.
	 * @ingroup DatabaseSchema
	 */
	protected $dbTable = '';

	/**
	 * Database colum name prefix.
	 * Database column names are generally prefixed somehow. This defines
	 * which is prefix to be used for columns.
	 *
	 * I'll fucking hate this, but we need to stay
	 * compatible with old ASP code.
	 * @ingroup DatabaseSchema
	 */
	protected $dbPrefix = '';

	/**
	 * Store for newId().
	 * Is used to prevent multiple IDs'.
	 */
	protected static $newId = array();

	/**
	 * Toggle switch for init.
	 * It tells later on if object is middle of construction.
	 * After object is loaded, it is set to false.
	 */
	protected $init = true;

	/**
	 * Toggle switch for telling if object was loaded from database.
	 */
	protected $loadedFromDb = false;

	/**
	 * Toggle switch to allow multiple calls to save().
	 */
	protected $_saved = false;

	/**
	 * Object visibility flag.
	 * Database table may contain table.visibible flag
	 * @code TINYINT( 1 ) NOT NULL DEFAULT 1 @endcode
	 * which is then used to store object visibility state.
	 * @see Visiblity Flags: \li Intra_Object::VISIBLE \li Intra_Object::HIDDEN \li Intra_Object::DELETED
	 */
	public $visible = Intra_Object::VISIBLE;

	/**
	 * Array of attributes to skip when exporting object.
	 * If subclasses needs to expand this list, it should be done in
	 * constructor. Example Person::__construct():
	 * @code
	 *	public function __construct() {
	 *		$this->skipAttributes = array_merge($this->skipAttributes, array(
	 *			'p_user',
	 *			'p_psw',
	 *			'_getMode'
	 *		));
	 *	}
	 * @endcode
	 * This is needed in cases like when exporting object for PDF.
	 */
	protected $skipAttributes = array(
		'dbTable',			///< Skip Intra_Object->dbTable.
		'dbPrefix',			///< Skip Intra_Object->dbPrefix.
		'loadedFromDb',		///< Skip Intra_Object->dbPrefix.
		'_saved',			///< Skip Intra_Object->_saved.
		'dbEmptyDate',		///< Skip Intra_Object->dbEmptyDate.
		'skipAttributes',	///< Skip Intra_Object->skipAttributes (self).
		'_accessors',		///< Skip Intra_Datastructure->_accessors.
		'_mutators',		///< Skip Intra_Datastructure->_mutators.
		'container',		///< Skip Intra_Object->container.
		'modified',			///< Skip Intra_Object->modified.
		'init',				///< Skip Intra_Object->init.
		'children'			///< Skip Intra_Helper->children (if exists).
	);

	/**
	 * Access compatibility parameter.
	 * Empty dates are converted to/from this.
	 * @ingroup DatabaseSchema
	 */
	public $dbEmptyDate = '1900-01-01 00:00:00';

	/**
	 * Constructor for Intra_Object.
	 */
	public function __construct($param=null) {
		// STUB
	}

	/**
	 * Serialization callback.
	 *
	 * Prepares object properties for serialization.
	 *
	 * @li If object is not loaded from database, unsets @c id.
	 * @li If object is modified (in Intra_Object::$modidied), adds new property Intra_Object->_modified.
	 * 
	 * @return
	 *   Array of object properties that should be serialized.
	 * @see Intra_Object::__wakeup()
	 * @see http://www.php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
	 */
	public function __sleep() {
		
		$attr = array_keys(get_object_vars($this));
		$attr = array_combine($attr, $attr);

		if(!$this->loadedFromDb) {
			unset($attr[$this->getPrimaryKey()]);
		}
		if(self::$modified[$this->container]->hasChildren($this->get('id'))) {
			$attr['_modified'] = '_modified';
		}

		return $attr;
	}

	/**
	 * Wakeup / unserialization callback.
	 * Handles wakeup routines:
	 * \li If has no id defined, gets new.
	 * \li Inserts self into Intra_Object::$objects container. And if Intra_Object->_modified is
	 *   set, adds self into modified container too.
	 */
	public function __wakeup() {
		if(!array_key_exists($this->container, self::$objects)) {
			self::$objects[$this->container] = new Intra_Helper();
		}

		if(!array_key_exists($this->container, self::$modified)) {
			self::$modified[$this->container] = new Intra_Helper();
		}

		if(!$this->get('id')) {
			$this->set('id', $this->newId());
		}

		self::$objects[$this->container]->addChildren($this);

		if($this->_modified) {
			self::$modified[$this->container]->addChildren($this);
			unset($this->_modified);
		}

		return $this;
	}

	/**
	 * Clone object.
	 * When cloning, generate new ID for object
	 */
	public function __clone() {
		if($this->get('id')) {
			$this->set('id', $id = $this->newId());
			self::$objects[$this->container]->addChildren($this);
			self::$modified[$this->container]->addChildren($this);
			$this->set('loadedFromDb', false);
			$this->set('_saved', false);
		}
	}

	/**
	 * Overloaded function for Intra_Helper->current().
	 * Instead where Intra_Helper->current() returns first current Intra_Helper->childer,
	 * we return self.
	 * @todo Conflicts with ArrayIterator::current().
	 */
	public function current() {
		return $this;
	}

	/**
	 * Intra_Datastructure->create_filter overloader.
	 * This includes datatime conversion for old access compatible dates.
	 * @see Intra_Object->dbEmptyDate
	 */
	protected function _createFilter($params) {
		$schema = $this->tableSchema();

		$processed = array();
		foreach($params as $key => &$val) {
			// Remove magic
			if(preg_match('/^[^a-z]+(.*)/i', $key, $match)) {
				$_key = $match[1];
			} else {
				$_key = $key;
			}

			switch($schema[$_key]['type']) {
				case 'datetime' :
					if(empty($val))
						$val = $this->dbEmptyDate;
					break;
			}
		}
		
		return parent::_createFilter($params);
	}

	/**
	 * Load object.
	 * 
	 * Main object loading mechanism. Loads object from database, or returns one from
	 * container, if loaded object had been loaded and modified before.
	 * 
	 * To load specified object, use id number:
	 * @code
	 *   // Returns intance of Invoice
	 *   $invoice = Invoice::load('34342354');
	 * @endcode
	 * 
	 * Or to query by parameters:
	 * @code
	 *   // Returns intance of Intra_Helper, which contains all invoice for company '157993261'.
	 *   $invoices = Invoice::load(array('c_id' => '157993261'));
	 * @endcode
	 * Previous can be accomplished with Company->invoices() shorthand function too:
	 * @code
	 *   $invoices = Company::load('157993261')->invoices();
	 * @endcode
	 * 
	 * @throws RuntimeException
	 *   Error in querying database.
	 * @param $param Mixed
	 *   Filter rules, for which to load. If is int, is considered
	 *   as primary key id, and changes return behaviour.
	 * @param $class
	 *   As long as php lacks support for static lazy binding, needs to
	 *   be class name to what object to load. (They are implemented in 5.3, but we need compatibility
	 *   for 5.2)
	 * @return
	 *   Intra_Helper class, with loaded objects as children, or if was requested by object key,
	 *   instance of param $class.
	 * @todo Check SQL error code, and if is temporary error, retry automaticly.
	 * @see TerraIntra_Object
	 */
	public function &load($param, $class = 'Intra_Object') {

		$sql = '';

		// Throw away class.
		$object = self::factory($class, array('id' => 0));

		if(!isset(self::$objects[$object->container]))
			self::$objects[$object->container] = new Intra_Helper();

		if (is_numeric($param) && $param != 0) {
			if($r = $object->mayLoadObject($param))
				return $r;

			$filter = Intra_Filter::factory();
			$filter->whereIn($object->getPrimaryKey(), $param);

		} elseif($param instanceOf Intra_Filter) {
			$filter = $param;
		} elseif(is_array($param) && count($param)) {
			$filter = $object->_createFilter($param);
    	} else {
			$filter = new Intra_Filter();
			self::debug('No load filter given, can cause troubles');
		}

		$schema = $object->tableSchema();

		$fields = array();
		foreach($schema as $field => $attr) {
			switch($attr['type']) {
				case 'point' :
					$_field = self::db()->quoteIdentifier($field);
					$fields[] = 'AsBinary('.$_field.') AS '.$_field;
					break;
				default :
					$fields[] = self::db()->quoteIdentifier($field);
					break;
			}
		}

		// Flag visibility check for afterwards
		$visibilityCheck = false;
		// Add visibility check rule if no such thing exitst
		if(!is_numeric($param) && $filter instanceOf Intra_Filter && !count($filter->fieldIdx['visible'])) {
			if($schema['visible']) {
				// Check visibility in SQL level
				$filter->whereIn('visible', Intra_Object::VISIBLE);
			} else {
				$visibilityCheck = true;
			}
		}

		$key = $object->getPrimaryKey();

		$sqlWhere = (string) $filter;
		if(empty($sqlWhere)) $sqlWhere = '1=1';

		// Skip modified items
		if(self::$modified[$object->container] && self::$modified[$object->container]->count()) {
			$skipList  = self::$modified[$object->container]->each()->get('id')->getChildren();
			$skipList  = array_filter($skipList);
			if(count($skipList))
				$sqlWhere .= sprintf(' AND {pre}_id NOT IN(%s)', implode(',', $skipList));
		}

		/// @todo Make locking selectable - for updates
		//$lock = 'FOR UPDATE';
		$lock = 'LOCK IN SHARE MODE';

		$sql = "SELECT ".implode(',', $fields)." FROM {table} WHERE $sqlWhere $lock";

		$sql = $object->rewriteSql($sql);

		$res =& self::db()->query($sql);
		if(self::db()->isError($res)) {
			throw new RuntimeException('Error while querying database: '.$res->getMessage().' SQL: '.$sql, $res->getCode());
			self::debug('Database query error: '.$res->getMessage().' SQL: '.$sql, E_USER_ERROR);
			return false;
		}

		$reset = false;
		$found = new Intra_Helper();

		while($attr = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$id = $attr[$key];
			if($r = $object->mayLoadObject($id)) {
				$found->addChildren($r);
			} else {
				// Build object

				$encAttr = array(
					'loadedFromDb' => true
				);

				foreach($attr as $col => $val) {
					switch($schema[$col]['type']) {
						case 'datetime' :
							if($val == $object->dbEmptyDate) $val = '';
							break;
					}

					$encAttr[$col] = $val;
				}


				$item =& self::factory($class, $encAttr);

				$found->addChildren($item);

				$cid = 'Intra:'.$object->container.':'.$item->get('id');
				Intra_CMS()->memcache()->add($cid, $item);
			}
		}


		if(is_numeric($param)) {
			return self::$objects[$object->container]->getChildren($param);
		} elseif(count($param) || $param instanceOf Intra_Filter) {
			if($visibilityCheck)
				$filter->whereIn('visible', Intra_Object::VISIBLE);

			/// TODO Remove those that _didn't_ match
			if(self::$modified[$object->container]->count()) {

				$modified = $filter->filterItems(self::$modified[$object->container]);
				if($modified->count()) {
					$modified->mergeChildrenTo($found);
					$filter->orderItems($found);
				}
			}

			return $found;
		} else {
			// Return all
			return self::$objects[$object->container];
		}
	}

	/**
	 * Load object from somewhere, but not from database.
	 * If object is stored in static Intra_Object::$container, load it
	 * from there. If it's not the already, try using memcache.
	 * 
	 * Necessary memcache functions should be provided in Intra_CMS() wrapper.
	 * ie, for drupal it's Intra_CMS_Drupal::memcache().
	 */
	public function mayLoadObject($id) {
		try {
			if(self::$objects[$this->container]->hasChildren($id)) {
				$r = self::$objects[$this->container]->getChildren($id);
				if($r) return $r;
			}

			// Try memcache
			$cid = 'Intra:'.$this->container.':'.$id;

			$r = Intra_CMS()->memcache()->get($id);
			$c = __CLASS__;
			if($r && $r instanceOf $c) {
				// Memcache de-serializes objects, so it should be added to container automaticly.
				self::debug('Memcache returned result for %s', $cid);
				return $r;
			}

		} catch(Exception $e) {
			// Wasn't loaded before :/
			Intra_CMS()->dfb($e);
		}
		return null;
	}

	/**
	 * Build an object.
	 * Object is added into static object registry.
	 * @param $class String
	 *   Class name to construct.
	 * @param $attr Array
	 *   Attributes to populate into object.
	 * @return Intra_Object
	 *   reference to constructed class from object registry.
	 */
	public function &factory($class=__CLASS__, $attr=array(), $self=false) {

		if($self || !method_exists($class, 'factory')) {

			$child = new $class;

			foreach($attr as $key => $val) {
				$child->set($key, $val);
			}

			if(($id = $child->get('id')) === null) {
				$child->set('id', $id = $child->newId());
			}

			if(!$child->container)
				$child->container = $self;

			if(!isset(self::$objects[$child->container])) {
				self::$objects[$child->container] = new Intra_Helper();
			}

			if(!isset(self::$modified[$child->container])) {
				self::$modified[$child->container] = new Intra_Helper();
			}

			if($id != 0) {
				self::$objects[$child->container]->addChildren($child, $id);
			}

			$child->init = false;

		} else {
			$self = $class; // XIIT
			$child = call_user_func(array($class, 'factory'), $class, $attr, $self);
		}

		return $child;
	}

	/**
	 * Save variables into database.
	 *
	 * Executes Insert/Update clause. To make changes, one needs to
	 * execute Intra_Helper::dbCommit() too.
	 * If Exception is rised, commit will be reverted.
	 * @throws RuntimeException If save was failure, and was reverted.
	 */
	public function save() {

		$transID = $this->saveBeginTransaction();
		try {
			// What to do?
			// Fucking thing sucks. PHP thinks that -1 == 1, so use ===
			if($this->visible === Intra_Object::DELETED) {
				$this->deleteObject();
				$this->deleteRelated();
			} else {
				$id = $this->get('id');
				$this->saveObject();
				$this->saveRelated();
				if(self::$modified[$this->container]->hasChildren($id))
					self::$modified[$this->container]->removeChildren($id);

			}
			$this->saveCommitTransaction($transID);
		} catch(RuntimeException $e) {
			self::db()->rollback($transID);
			throw $e;
			return false;
		}
		$cid = 'Intra:'.$this->container.':'.$this->get('id');
		Intra_CMS()->memcache()->delete($cid);

		return $this;
	}

	/**
	 * Perform raw sql insert/update.
	 * Even as it is public, do not use in Controller code, where
	 * one should use Intra_Object::save() call.
	 */
	public function saveObject() {
		$cols = $this->asDBColums();
		if(!$this->get('loadedFromDb') && !$this->_saved) {
			// New object
			$sql  = 'INSERT INTO {table} (';
			$sql .= implode(', ', array_keys($cols));
			$sql .= ') VALUES (';
			$sql .= implode(', ', $cols);
			$sql .= ')';
		} else {
			$id = self::db()->quote($this->get('id'));
			// Update existing
			$sql = 'UPDATE {table} SET ';
			foreach($cols as $key => $val) {
				$entrys[] = $key.' = '.(string) $val;
			}
			$sql .= implode(', ', $entrys).' WHERE {pre}_id ='.$id;
		}
		$sql = $this->rewriteSql($sql);
		$res = $this->db()->query($sql);

		if(PEAR::IsError($res)) {
			throw new RuntimeException('Error while saving object '.get_class($this).'->('.$id.') into database. Error: '.$res->getMessage().' SQL: '.$sql, $res->getCode());
		}

		$this->_saved = true;
		$cid = 'Intra:'.$this->container.':'.$this->get('id');
		Intra_CMS()->memcache()->delete($cid);
	}

	/**
	 * Implementable helper function.
	 * This should trigger related item saving, by calling:
	 * @code
	 * $this->getChildren()->each()->saveObject();
	 * @endcode
	 * Note that transaction preparations is handled by $Intra_Object->save()
	 * call.
	 */
	protected function saveRelated() {}

	protected function saveBeginTransaction() {
		$transID = get_class($this).'_'.$this->get('id');
		$this->db()->beginTransaction($transID);
		return $transID;
	}

	/**
	 * Commit transaction.
	 * @param $transID
	 *   Transaction ID to commit.
	 */
	protected function saveCommitTransaction($transID) {
		$this->db()->commit($transID);
	}

	/**
	 * @name Object deletion
	 * @{
	 * 
	 * Object deletion is an over complicated procedure.
	 * It's so complicated actually, that there should be
	 * manager instance for it.
	 */

	/**
	 * Deletes object from container and database.
	 * Doesn't destroy self.
	 */
	public function delete() {
		$id = $this->get('id');
		$transID = false;
		if($this->get('loadedFromDb')) {
			$transID = $this->saveBeginTransaction();
		}

		try {

			$this->deleteObject();

			if($transID)
				$this->saveCommitTransaction($transID);

		} catch(RuntimeException $e) {
			if($transID)
				self::db()->rollback($transID);

			throw $e;
			return false;
		}
	}

	/**
	 * Delete object from datastores.
	 */
	public function deleteObject() {

		$id = $this->get('id');
		$cid = 'Intra:'.$this->container.':'.$id;

		if($this->get('loadedFromDb')) {
			$this->deleteDbObject();
		}

		$this->visible = Intra_Object::DELETED;
//		self::$objects[$this->container]->removeChildren($cid);
		Intra_CMS()->memcache()->delete($cid);
	}

	/**
	 * Delete object from database.
	 * @return Bool
	 * @throw RuntimeException Error in database query.
	 */
	protected function deleteDbObject() {
		$id = $this->get('id');

		$sql = 'DELETE FROM {table} WHERE {pre}_id = '.$this->db()->quote($id);
		$sql = $this->rewriteSql($sql);
		$res = $this->db()->query($sql);

		if(Pear::isError($res)) {
			throw new RuntimeException('Error while deleting object '.get_class($this).'->('.$id.') from database. Error: '.$res->getMessage().' SQL: '.$sql, $res->getCode());
			return false;
		}
		return true;
	}
	/**
	 * Implementable helper function.
	 * This should trigger related item deletion, by calling:
	 * @code
	 * $this->getChildren()->each()->deleteObject();
	 * @endcode
	 * Note that transaction preparations is handled by $Intra_Object->delete()
	 * call.
	 */
	protected function deleteRelated() {}

	/**
	 * @} End of "Deletion"
	 */

	/**
	 * Generate new ID, similar what old intra used -- so it sucks.
	 * First, try to get last ID from shared memory. If not exists,
	 * fetch from database. If not still exists, create one using old
	 * Intra style.
	 * @todo Shared memory is currently b0rked.
	 * @todo Maybe should use memcache?
	 */
	public function newId($table = null) {
		if($table === null) $table = $this->mainTable();

		$id = 0;
		// SHM doesn't work
		if(function_exists('shm_get_var') && false) {
			// Try to fetch from shared memory
			if(!isset(self::$newId)) {
				self::debug('Initializing shared memory segment');
				$shmKey = ftok(__FILE__, 'i');

				self::$newId = shm_attach($shmid, 10240);
			}

			$id = @shm_get_var(self::$newId, $table);
			if($id) {
				$id++;
				shm_put_var(self::$newId, $table, $id);
				return $id;
			}
		} elseif(is_array(self::$newId) && array_key_exists($table, self::$newId)) {
			self::$newId[$table]++;
			return self::$newId[$table];
		}

		// Try max() from database
		$sql = $this->rewriteSql('SELECT MAX({pre}_id) FROM '.$table);

		$res =& self::db()->query($sql);

		list($id) = $res->fetchRow();
		if($id) {
			$id++;
		} else {
			// Generate totally new ID, using intra compatible function
			// NewID = (31104400 * (Year(Date())-1997)) + (259200 * Month(Date())) + (86400 * Day(Date())) + (3600 * Hour(Time())) + (60 * Minute(Time())) + Second(Time())

			$now = time();
			$id  = 31104400 * (date('Y',$now)-1997);
			$id += 259200 * date('m',$now);
			$id += 86400 * date('d',$now);
			$id += 3600 * date('H',$now);
			$id += 60 * date('i',$now);
			$id += date('s',$now);
		}

		if(function_exists('shm_put_var') && false) {
			// Save into shared memory
			shm_put_var(self::$newId, $table, $id);
		} else {
			if(!is_array(self::$newId)) self::$newId = array();
			self::$newId[$table] = $id;
		}

		return $id;
	}

	/**
	 * Format fields for database.
	 * One ugly pile of code.
	 * @todo use mutators
	 */
	protected function asDBColums() {

		$fields = $this->tableFields();
		$cols = array();
		$schema = $this->tableSchema();

		foreach($fields as $key => $field) {
			if($this->init) {
				// When constructing new class, use stub stuff
				if(property_exists($this->$key)) {
					$val = $this->$key;
				} else {
					$val = null;
				}
			} else {
				$val = $this->get($key);

				if($schema[$key]['null'] && ($val === null || $val === '')) {
					$val = 'NULL';
				} else {
					switch($schema[$key]['type']) {
						case 'datetime' :
							if(empty($val)) $val = $this->dbEmptyDate;
							$val = self::db()->quote($val);
							break;
						case 'point' :
							if(is_object($val)) {
								$val = (string) $val;
								break;
							}
						case 'int' :
						case 'tinyint' :
							$val = (int) $val;
							break;
						default:
							if(is_array($val))
								$val = implode(', ', $val);

							$val = self::db()->quote($val);
							break;
					}
				}
			}

			$cols[$field] = $val;

		}
		return $cols;
	}

	/**
	 * Fetch table fields
	 */
	protected function tableFields($prefix=null) {
		$fields = array_keys($this->tableSchema());

		if ($prefix !== null) {
			$columns = array();
			foreach ($fields as $field) {
				$columns[$field] = self::db()->quoteIdentifier($prefix).'.'.self::db()->quoteIdentifier($field);
			}
		} else {
			foreach($fields as $field) {
				$colums[$field] = self::db()->quoteIdentifier($field);
			}
		}

		return $colums;
	}

	/**
	 * Fetch database schema
	 */
	protected function tableSchema($table=null) {
		static $r;
		if($table === null) $table = $this->mainTable();
		if(isset($r[$table])) return $r[$table];
		$r[$table] = array();
		$res =& self::db()->query('DESCRIBE '.self::db()->quoteIdentifier($table));
		if(PEAR::IsError($res)) {
			throw new Exception($res->getMessage());
		}

		while($row =& $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {

			if(!preg_match('/(\w+)\((\d+)\)/', $row['type'], $type)) {
				$type[1] = $row['type'];
				$type[2] = '';
			}
			$r[$table][strtolower($row['field'])] = array(
				'type' 		=> $type[1],
				'length'	=> $type[2],
				'null'		=> ($row['null'] == 'YES') ? true : false
			);
		}
		return $r[$table];
	}

	/**
	 * Return main table indentifier.
	 */
	protected function mainTable() {
		if(!is_array($this->dbTable)) {
			$tables['table'] = $this->dbTable;
		} else {
			$tables = $this->dbTable;
		}
		if($tables['table'])
			return $tables['table'];
		reset($tables);
		$table = array_shift($tables['table']);
		self::debug('No default table defined, returning %s as main table', $table);
		return $table;
	}

	/**
	 * Return primary ID.
	 */
	public function getPrimaryKey() {
		return $this->dbPrefix.'_id';
	}

	public function get($key) {
		if($key == 'id')
			$key = $this->getPrimaryKey();

		if(($r = parent::get($key)) === null && !$this->init)
			self::debug('No such variable defined: %s', $key);

		return $r;
	}

	/**
	 * Set object value.
	 * If is called outside init, is pushed to "modified"
	 * container.
	 * @see Intra_Object::$modified
	 */
	public function set($key, $val) {
		if($key == 'id')
			$key = $this->getPrimaryKey();

		if(!$this->init) {
			Intra_CMS()->dfb('Flaggin '.$this->container. 'as modified');

			self::$modified[$this->container]->addChildren($this);
		}

		return parent::set($key, $val);
	}

	/**
	 * Return public attributes
	 */
	public function attributes($object=null) {
			// A bit stupid hack, to show only public variables
		if($object == null) {
			return $this->attributes($this);
		}

		$attr = array_keys(get_object_vars($object));
		$attr = array_diff($attr, $this->skipAttributes);

		$r = array();

		foreach($attr as $val) {
			$key = preg_replace('/^('.preg_quote($this->dbPrefix,'/').'_)/', '', $val);
			$r[$key] = $val;
		}

		return $r;
	}

	/**
	 * Rewrite SQL.
	 * @ingroup Database
	 *
	 */
	public function rewriteSql($sql) {
		$tables = array();
		if($this->dbTable) {
			if(!is_array($this->dbTable)) {
				$tables['table'] = $this->dbTable;
			} else {
				$tables =  $this->dbTable;
			}

			foreach($tables as $key => $table) {
				$sql = strtr($sql, array('{'.$key.'}' => self::db()->quoteIdentifier($table)));
			}
		}

		if($this->dbPrefix) {
			$sql = strtr($sql, array('{pre}' => $this->dbPrefix));
		}

		$sql = strtr($sql, array('{' => '', '}' => ''));

		$args = func_get_args();
		array_shift($args);
		if (isset($args[0]) && is_array($args[0])) { // 'All arguments in one array' syntax
			$args = $args[0];
		}
		if($args) {
			_db_query_callback($args, TRUE);
			$sql = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $sql);
		}

		return parent::rewriteSql($sql);
	}

	/**
	 * Wrap self around view, and return it as string.
	 * This should save some keystrokes.
	 * @since 2010-05-07
	 */
	public function __toString() {
		try {
			$view = Intra_View::Factory($this);
			return (string) $view;
		} catch(Exception $e) {
			Intra_CMS()->dfb($e);
			return $this->container.':'.$this->get('id');
		}
	}
}
