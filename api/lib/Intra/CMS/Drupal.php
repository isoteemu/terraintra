<?php
/**
 * @file
 *   Cross-drupal calls
 */

class Intra_CMS_Drupal extends Intra_CMS_Common implements Intra_CMS_Interface {

	public function init() {
		parent::init();
		if(function_exists('dfb'))
			$this->overrides['dfb'] = 'dfb';
		else
			$this->overrides['dfb'] = array(&$this, 'dfb');

		if(function_exists('intra_api_currentuser'))
			$this->overrides['currentuser'] = 'intra_api_currentuser';

		$this->overrides['memcache'] = array(&$this, 'memcache');
		$this->overrides['message'] = array(&$this, 'message');
		$this->overrides['filter'] = array(&$this, 'filter');
//		$this->overrides['cache']    = array(&$this, 'cache');
	}

	/**
	 * Try detecting drupal
	 */
	public static function detect() {
		if(defined('DRUPAL_BOOTSTRAP_SESSION') && defined('VERSION')) return VERSION;
		elseif(defined('DRUPAL_BOOTSTRAP_SESSION')) return '4.99';

		return false;
	}

	public function dfb() {
		$args = func_get_args();

		global $user;
		if($user->uid) {
			$fb = $this->callManager->FirePHP($args);
		}
	}

	public function filter($content, $input_format=null) {
		if((strstr($content, '<') && $input_format === null) || $input_format === Intra_CMS_Common::FORMAT_HTML) {
			$format = variable_get('intra_system_filter_format', 2);
		} else {
			$format = FILTER_FORMAT_DEFAULT;
		}
		$format = filter_resolve_format($format);

		return check_markup($content, $format, FALSE);
	}

	public function message(Intra_Object $object, $message='', $replacements=array()) {

		$name = (defined('INTRA_API_NAME')) ? INTRA_API_NAME : 'TerraINTRA';

		$link = array();
		try {
			$view = Intra_View::factory($object);
			if($view && function_exists('intra_api_url')) {
				$link[] = l(get_class($object), intra_api_url($object));
			}
		} catch(Exception $e) {}

		watchdog($name, decode_entities((string) $message), (array) $replacements, WATCHDOG_INFO, implode(" ", $link));

		$formated = t($message, $replacements);

		// Cleanup
		$reg = array(
			'/\s{2,}/' => ' ',
		);
		$formated = str_replace("\n", "", $formated);
		$formated = preg_replace(array_keys($reg), $reg, $formated);
		$formated = trim($formated);

		drupal_set_message($formated, 'info');
		if(module_exists('privatemsg')) {
			$this->_message_privatemsg($object, $formated);
		}

	}

	protected function _message_privatemsg($object, $message) {
		static $admins;
		if(!$admins) {
			$admins = array();
			$companys = Company::load(array(
				'c_type' => Company::TYPE_ADMIN
			));

			Intra_CMS()->currentuser()->getCompany()->mergeChildrenTo($companys);

			$c_ids = $companys->each()->get('c_id')->getChildren();

			$people = Person::load(array(
				'p_type'	=> Person::TYPE_ADMIN,
				'c_id'		=> $c_ids,
				'!p_id'		=> Intra_CMS()->currentuser()->get('p_id'),
				'!p_user'	=> '' // Username not null,
			));

			foreach($people as $person) {
				$user = $this->_message_drupaluser($person);
				if($user)
					$admins[$person->get('p_id')] = $user;
			}

		}

		$recipients = $admins;
		$body = $message;
		$subject =  strip_tags($message);
		$additional = array();
		$class = intra_api_getclass($object, array('Company', 'Person'));
		switch($class) {
			case 'Company' :
				// Notify company contact and prospect owner
				if($contact = $object->getContact())
					$additional[] = $contact;
				try {
					if($p_id = $object->get('prospect_of')) {
						$person = Person::load($p_id);
						$additional[] = $person;
						$additional += $person->getCompany()->people(array(
							'p_type' => Person::TYPE_DISTRIBUTOR
						))->getChildren();
					}
				} catch(Exception $e) {}

				break;
			case 'Person' :
				$additional[] = $object;
				$additional[] = $object->getCompany()->getContact();
				try {
					if($p_id = $object->getCompany()->get('prospect_of')) {
						$person = Person::load($p_id);
						$additional[] = $person;
						$additional += $person->getCompany()->people(array(
							'p_type' => Person::TYPE_DISTRIBUTOR
						))->getChildren();
					}
				} catch(Exception $e) {}

				break;
		}

		foreach($additional as $person) {
			if(!($person instanceof Person)) continue;
			$user = $this->_message_drupaluser($person);
			if($user)
				$recipients[$person->get('p_id')] = $user;
		}

		try {
			$view = Intra_View::factory($object);
			$body = t('<div>!body</div><div>View: !intra_object', array(
				'!body' => $body,
				'!intra_object' => (string) $view
			));

		} catch(Exception $e) {}

		$filter = filter_resolve_format(variable_get('intra_system_filter_format', 2));

		$threat = privatemsg_new_thread($recipients, $subject, $body, array(
			'format' => $filter
		));

		// Add tags
		if(module_exists('privatemsg_filter') && $threat['success']) {
			$tags = array();
			$tags[] = (defined('INTRA_API_NAME')) ? INTRA_API_NAME : 'TerraINTRA';
			$tags[] = get_class($object);
			if($view instanceof Intra_View_Microformat) {
				$tags[] = decode_entities(strip_tags((string) $view));

				if($object instanceof Person) {
					$tags[] = decode_entities(strip_tags((string) $view->getCompany()));
				}
			}

			$tag_ids = privatemsg_filter_create_tags($tags);

			foreach($threat['message']['recipients'] as $recipient) {
				foreach($tag_ids as $tag_id) {
					privatemsg_filter_add_tags($threat['message']['thread_id'], $tag_id, $recipient);
				}
			}

		}

	}

	protected function _message_drupaluser(Person $person) {
		static $cache = array();
		$id = $person->get('p_id');

		if(!isset($cache[$id])) {
			$name = $person->get('p_user');
			if(!empty($name))
				$cache[$id] = user_load(array('name' => $name));
			else
				$cache[$id] = false;
		}
		return $cache[$id];
	}

	public function &cache() {

	}

	/**
	 * Setup memcache servers
	 *
	 * @todo Implement prefixing
	 */
	public function &memcache() {
		static $inited = false;
		$memcache =& $this->callManager->memcache();

		if(!$inited) {
			$inited = true;

			$servers = variable_get('memcache_servers', array('127.0.0.1:11211' => 'default'));
			$bins = variable_get('memcache_servers', array('cache' => 'default'));

			$cluster = empty($bins['intra']) ? 'default' : $bins['intra'];
			foreach($servers as $server => $_cluster) {
				if($_cluster != $cluster) continue;

				list($host, $port) = explode(':', $server);
				$memcache->addServer($host, $port);
			}
		}

		return $memcache;
	}

}
