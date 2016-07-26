<?php
/**
 * Throttle Manager provides class to determine,
 * if some time-expensive task is worth the effort.
 */
class Intra_View_Plugin_ThrottleManager extends Intra_View_Plugin {

	/**
	 * @addtogroup Constants
	 * Is throttling enabled or disabled
	 * @{
	 */
	const ENABLED  = true;
	const DISABLED = false;

	/**
	 * @} End of "Constants"
	 */
	/**
	 * How long can be time wasted before throttling.
	 * This is a float, in seconds.
	 */
	public $timeToSpent = 1.0;

	/**
	 * How much time has been spent
	 */
	public static $timeSpent = 0.0;

	/**
	 * Internal timer.
	 * Stores current running start-time.
	 */
	private $timer = 0.0;

	public $target;

	public function init() {
		// If requested by ajax, throttle everything
		if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			$this->timeToSpent = 0.0;
		} if(module_invoke('throttle', 'status')) {
			// Throttling enabled
			$this->timeToSpent = 0.0;
		}
	}

	/**
	 * Return clone of Intra_View_Plugin_ThrottleManager instance
	 */
	public function ThrottleManager(&$target=null) {
		$instance = clone $this;
		if($target) {
			$instance->target =& $target;
		}

		return $instance;
	}

	/**
	 * Return throttling status.
	 * @return
	 *   If enabled, Intra_View_Plugin_ThrottleManager::ENABLED
	 *   or disabled, Intra_View_Plugin_ThrottleManager::DISABLED
	 */
	public function status() {
		if(self::$timeSpent >= $this->timeToSpent) {
			return Intra_View_Plugin_ThrottleManager::ENABLED;
		} else {
			return Intra_View_Plugin_ThrottleManager::DISABLED;
		}
	}

	public function startTimer() {
		if($this->timer > 0) {
			$this->stopTimer();
		}

		list($usec, $sec) = explode(' ', microtime());
		$this->timer = (float)$usec+(float)$sec;

		return $this;
	}

	/**
	 * Stop currently running timer, and add it into a $this->timeSpent.
	 */
	public function stopTimer() {
		if($this->timer >= 0) {
			list($usec, $sec) = explode(' ', microtime());
			$diff = ((float)$usec+(float)$sec) - $this->timer;

			self::$timeSpent += $diff;
		}
		$this->timer = 0;
		return $this;
	}

	/**
	 * Run function if not throttling.
	 * @return Mixed
	 *   Returns null if throttling, else whatever function call returns.
	 */
	public function __call($func, $args) {
		if($this->status() == Intra_View_Plugin_ThrottleManager::ENABLED) {
			return null;
		}

		$this->startTimer();

		if($this->target)
			$target =& $this->target;
		else
			$target =& $this->_view;

		$r = call_user_func_array(array(&$target, $func), $args);

		$this->stopTimer();

		return $r;
	}

	/**
	 * Invoke function, or throttle if seen fit.
	 * @param $target
	 *   Callback to run, if not throttling
	 */
	public function invoke($target) {

		if($this->status() == Intra_View_Plugin_ThrottleManager::ENABLED) {
			return null;
		}

		$this->startTimer();

		$arguments = func_get_args();
		// Remove first argument (target)
		array_shift($arguments);

		$return = call_user_func_array($target, $arguments);

		$this->stopTimer();

		return $return;

	}

}
