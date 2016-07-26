<?php
/**
 * @file
 *   Cross-cms utility call wrapper.
 */
class Intra_CMS {

	/**
	 * Different CMS constants
	 */
	const DETECT	= -1;	/// Try detecting
	const UNKNOWN	= 0;	/// Unkwnown CMS
	const DRUPAL	= 1;	/// Drupal
	const HORDE		= 2;	/// Horde

	private $cms;

	/**
	 * Registry of known CMS's and their classes.
	 * Value should be a valid class, and it should
	 * implement static detect method.
	 * @see Intra_CMS_Common::detect()
	 */
	public static $cmsRegistry = array(
		Intra_CMS::UNKNOWN	=> 'Intra_CMS_Common',
		Intra_CMS::DRUPAL	=> 'Intra_CMS_Drupal',
		Intra_CMS::HORDE	=> 'Intra_CMS_Horde'
	);

	public function __construct($cms) {
		if(!isset(self::$cmsRegistry[$cms]))
			throw new UnexpectedValueException('Unknown CMS type: '.(string) $cms);

		$c = self::$cmsRegistry[$cms];
		$this->cms = new $c();

		// Initiate callManager
		$this->cms->callManager = new Intra_CMS_CallManager();

		// Initiate main CMS class
		if($this->cms instanceOf Intra_CMS_Interface)
			$this->cms->init();

	}

	/**
	 * Call CMS function.
	 * If function is not available, try loading
	 * appropriate include
	 * @throws RuntimeException
	 *   If function is not callable, and non recovable.
	 */
	private function __call($func, $args) {

		if($this->cms->overrides[$func]) {
			return call_user_func_array($this->cms->overrides[$func], $args);
		} else {
			return $this->cms->callManager->call($func, $args);
		}
	}

	public static function factory($cms = Intra_CMS::DETECT) {
		if($cms == Intra_CMS::DETECT) {
			list($cms, $versio) = self::detectCMS();
		}
		$instance = new Intra_CMS($cms);
		return $instance;

	}

	/**
	 * Try detecting running CMS
	 * Goes thru self::$cmsRegistry as reversed, and returns first detected
	 * CMS
	 * @return Array
	 *   First value is registry ID, and second is reported versio.
	 */
	private function detectCMS() {
		foreach(array_reverse(self::$cmsRegistry, true) as $cms => $class) {
			try {
				$versio = call_user_func(array($class, 'detect'));

				if($versio) {
					return array(
						$cms,
						$versio
					);
				}
			} catch(Exception $e) {}
		}
		return array(Intra_CMS::UNKNOWN, 0);
	}

	/**
	 * Return currently running CMS
	 */
	public function getCMSType() {
		if($this->cms) {
			$class = get_class($this->cms);
			$cms = array_search($class, self::$cmsRegistry);

			if($cms === false)
				return Intra_CMS::UNKNOWN;

			return $cms;
		} else {
			$cms = $this->detectCMS();
			return $cms[0];
		}
	}

}

/**
 * Shorthand function to perform singleton -style calls to Intra_CMS class.
 */
if(!function_exists('Intra_CMS')) {
	function &Intra_CMS() {
		static $cms;
		if(!$cms) {
			$cms = Intra_CMS::factory(Intra_CMS::DETECT);
		}
		return $cms;
	}
}