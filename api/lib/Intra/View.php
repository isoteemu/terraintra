<?php
/**
 * @defgroup TerraIntra_View Intra Object Views
 * Intra_View is used to provide additional layer for Intra_Object properties.
 */
/**
 * @page TerraIntra_View_Usage Usage examples of View objects.
 * @ingroup TerraIntra_View
 * To use existing view for Person in HTML page:
 * Outputs firstname and lastname, as implemented in Intra_View_Microformat_Person.
 * @code
 * $person = Person::load(array(
 *   'p_fname' => 'Hannu',
 *   'p_lanem' => 'Korpela'
 * ))->current();
 * echo Intra_View::factory($person);
 * @endcode
 * Outputted microformatted html looks like:
 * @code
 * <span class="vcard person" data-uid="12345">
 *   <span class="fn">
 *     <span class="first-name">Hannu</span> <span class="last-name">Korpela</span>
 *   </span>
 * </span>
 * @endcode
 * 
 * To get some specific attributes:
 * @code
 * $view = Intra_View::factory($person);
 * echo $person->get('p_street');
 * @endcode
 * If view object implements \c p_street accessor, it is used. If no such accessor is implemented,
 * parent object - \ref Person - is used directly, and similar call is delegated to it. In later case,
 * it would mean that p_street address is returned, but it wouldn't contain semantic markup.
 */

/**
 * @page TerraIntra_View_Usage_Plugin Using Intra_View plugins
 * @ingroup TerraIntra_View
 * Plugins are used to alter View_Object. Example, Intra_View_Plugin_AsText (which strips html formating
 * and replaces with plaintext):
 * @code
 * $person = Person::load('12345');
 * $view = Intra_View::factory($person);
 * // Returns "Hannu Korpela", as implemented in Intra_View_Microformat_Person::__toString(),
 * // but without semantic markup.
 * echo $view->asText();
 * @endcode
 * Or, plugins can implement more behaviours. Using example Intra_View_Plugin_Favicon.
 * @code
 * $company = Company::load(12345);
 * echo Intra_View::factory($company)->favicon()->icon();
 * @endcode
 * would check Company::$c_url, and return small favicon for it.
 */

/**
 * Dirty view wrapper for Intra_Object.
 * @ingroup TerraIntra_View
 * @see TerraIntra_View
 */
class Intra_View {

	/// Class prefix Intra_View for plugins
	static $classPrefix = 'Intra_View_Plugin_';

	/// Reference object container
	private $_reference;

	public static $viewTypes = array(
		'text/html' => 'Microformat',
	);

	/**
	 * List of classes which have views
	 */
	public static $viewClasses = array(
		'Company',
		'Invoice',
		'Agreement',
		'Person',
		'Person_Email'
	);

	public function __construct(Intra_Object $object) {
		$this->setReference($object);
		$this->init();
	}

	protected function init() {}

	public function setReference($object) {
		// We can't set reference directly (by using pointer to given object),
		// as it can be changed later on. So work around it.
		$this->_reference =& Intra_Object::load($object->get('id'), get_class($object));
	}

	public function &getReference() {

		if(!isset($this->_reference))
			throw new Exception('Missing reference object');

		return $this->_reference;
	}

	protected $_plugins = array();

	public function __call($func, $args) {
		$plugin = $this->preparePlugin($func);
		if($_plugin = $this->getPlugin($plugin)) {
			return call_user_func_array(array(&$_plugin,$func), $args);
		} elseif(method_exists($this->getReference(), $func)) {
			return call_user_func_array(array($this->getReference(),$func), $args);
		} else {
			throw new BadFunctionCallException('No such function '.$func.' in class '.get_class($this).' and no appropriate plugin');
		}
	}

	/**
	 * @return String name of plugin at registry
     */
	public function preparePlugin($name) {
		$name = ucwords($name);
		if(!isset($this->_plugins[$name])) {
			$class = sprintf('%s%s', self::$classPrefix, $name);
			if(class_exists($class))
				$this->_plugins[$name] = new $class($this);
			else
				$this->_plugins[$name] = null;
		}
		return $name;
	}

	/**
	 * Test if plugin is callable
	 */
	public function &getPlugin($plugin) {
		return (isset($this->_plugins[$plugin]) && is_object($this->_plugins[$plugin])) ? $this->_plugins[$plugin] : false;
	}

	/**
	 * Wrap object into view.
	 * @param $object
	 *   Object to wrap around view
	 * @param $cacheable
	 *   Can result be cached, and/or return from cache
	 * @type
	 *   Mime-type for view object.
	 */
	public static function factory(Intra_Object &$object, $cacheable=true, $type = null) {
		static $cache = array();

		/// TODO: Make registry
		$class = self::get_class($object);
		$cid = $object->get('id');

		if($type == null) {
			$type = 'text/html';
			// Pear for content negoation
			@include_once 'HTTP.php';
			if(class_exists('HTTP')) {
				$http = new HTTP();
				$type = $http->negotiateMimeType(array_keys(self::$viewTypes), $type);
			}
		} else {
			$cacheable = false;
		}

		if(!$cacheable || !isset($cache[$class][$cid])) {

			$prefix = self::$viewTypes[$type];
			$classname = sprintf('Intra_View_%s_%s', $prefix, $class);

			if(!class_exists($classname)) {
				$classname = sprintf('Intra_View_%s', $prefix);
			}

			$item = new $classname($object);

			if($cacheable)
				$cache[$class][$cid] = $item;

		} else {
			$id = $cache[$class][$cid]->getReference()->get('id');
			if($cid != $id) {
				throw new UnexpectedValueException('View object reference does not match what we expected. Cache corrupted.');
			}

			$item = $cache[$class][$cid];
		}
		return $item;
	}

	public static function addViewClass($class) {
		self::$viewClasses[] = $class;
	}

	/**
	 * TODO Improvements required
	 */
	protected static function get_class($object) {
		static $cache=array();
		$realClass = $class = get_class($object);

		if(isset($cache[$realClass]))
			return $cache[$class];

		$classlist = self::$viewClasses;

		while($class) {
			if(in_array($class, $classlist)) {
				return $cache[$realClass] = $class;
				break;
			}
			$class = get_parent_class($class);
		}

		throw new UnexpectedValueException('Did not find suitable class for '.get_class($object));
		return false;
	}

}