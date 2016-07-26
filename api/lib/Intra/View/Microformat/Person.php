<?php

define('GOOGLE_APPKEY', 'ABQIAAAAHY20UADqq1e5eaewOJBszBS_wNnwIY7-FCgtQRYOx2sDGxZgDhRz9myYYrT5ff7u77qGjwpWBTv3EA');

class Intra_View_Microformat_Person extends Intra_View_Microformat {

	protected $_accessors = array(
		'p_fname'		=> 'getFirstName',
		'p_lname'		=> 'getLastName',
		'p_street'		=> 'getStreet',
		'p_box'			=> 'getBox',
		'p_city'		=> 'getLocality',
		'p_zip'			=> 'getZip',
		'p_country'		=> 'getCountry',
		'p_email'		=> 'getEmail',
		'p_skype'		=> 'getSkype',
		'p_dname'		=> 'getDepartment',
		'p_type'		=> 'getType',
		'p_photo'		=> 'getPhoto',
		'p_class'		=> 'getClasses',
		'p_user'		=> 'getUser',
		'p_rem'			=> 'getNote',
	);

	public function get($key) {
		$old = $this->getReference()->getMode(Person::GETMODE_RECURSE);
		$r = parent::get($key);
		$this->getReference()->getMode($old);

		return $r;
	}

	public function getFirstName() {
		return $this->tag('given-name', $this->getReference()->get('p_fname'));
	}

	public function getLastName() {
		return $this->tag('family-name', $this->getReference()->get('p_lname'));
	}

	public function getFn() {
		$fname = (string) $this->get('p_fname');
		$lname = (string) $this->get('p_lname');
		$name =  t('!fname !lname', array('!fname' => $fname, '!lname' => $lname));

		if(function_exists('intra_api_url') && $url = intra_api_url($this->getReference())) {

			$tag = Intra_View_Microformat_Tag::factory($name, 'a', false);
			$tag->setAttribute('href', url($url));

			if(module_exists('votingapi')) {
				$tag->setAttribute('ping', url($url.'/ping'));
			}
			$tag->addClass('url');
		} elseif($email = $this->getReference()->get('p_email')) {
			$tag = Intra_View_Microformat_Tag::factory($name, 'a', false);
			$tag->setAttribute('href','mailto:'.$email);
			$tag->addClass('email');
		} else {
			$tag = Intra_View_Microformat_Tag::factory($name, 'span', false);
		}

		$tag->addClass('fn n');

		return $tag;
	}

	public function getStreet() {
		return $this->tag('street-address', $this->getReference()->get('p_street'));
	}

	public function getBox() {
		return $this->tag('post-office-box', $this->getReference()->get('p_box'));
	}

	public function getZip() {
		return $this->tag('postal-code', $this->getReference()->get('p_zip'));
	}

	public function getLocality() {
		return $this->tag('locality', $this->getReference()->get('p_city'));
	}

	public function getCountry() {
		$country = $this->getReference()->get('p_country');

		$tag = Intra_View_Microformat_Tag_Country::factory($country);
		$tag->addClass('country-name');

		return $tag;
	}

	public function getEmail() {
		$mail = $this->getReference()->get('p_email');
		if($mail) {
			$tag = Intra_View_Microformat_Tag::factory($mail, 'a');
			$tag->addClass('email');
			// $mail = sprintf('"%s" <%s>', (string) $this->getFn(), $mail);
			$tag->addAttribute('href', sprintf('mailto:%s', $mail));
			return $tag;
		}
	}

	public function getSkype() {
		if($skype = $this->getReference()->get('p_skype')) {
			$tag = Intra_View_Microformat_Tag::factory($skype, 'a');
			$tag->addClass('url');

			$tag->setAttribute('href', 'skype:'.$skype.'?call');
			$status = $this->image('http://mystatus.skype.com/smallicon/'.$skype);
			$status->setAttribute('unselectable', 'on')->iconSmall();

			return $status->__toString() . $tag->__toString();
		} else {
			return;
		}
	}

	public function getDepartment() {
		return $this->tag('organization-unit', $this->getReference()->get('p_dname'));
	}

	public function getType() {
		$type = $this->getReference()->get('p_type');
		$code = Codes::getCode('P_TYPE', $type);
		if($type && $code)
			$tag = $this->tag('contact-type-'.$code->get('cd_value'), t($code->get('cd_name')));
		return $tag;
	}

	public function getCompany() {
		return Intra_View::factory($this->getReference()->getCompany());
	}

	/**
	 * Temporary hack for photo
	 */
	public function getPhoto() {

		$img = $this->Photo();

		if(!$img) {
			$src = drupal_get_path( 'module', 'intra_api').'/image/im-user.png';
			$img = $this->image($src);
		}

		$img->addClass('photo');
		$img->setAttribute('alt', (string)$this->asText()->getFn());

		return $img;
	}

	/**
	 * Convert company codes into human readable values
	 */
	public function getClasses() {
		static $cache;
		if(!isset($cache)) {
			$search = module_exists('intra_search_contact');
			$tagName = ($search) ? 'a' : 'span';

			foreach(Codes::map('P_CLASS') as $code) {
				$tag = Intra_View_Microformat_Tag_Tag::factory(t($code->get('cd_name')), $tagName);
				$tag->addClass('contact-class-'.$code->get('cd_value'));

				if($search)
					$tag->setAttribute('href', url('search/intra_search_contact/p_class:'.urlencode($code->get('cd_value'))));

				$cache[$code->get('cd_value')] = $tag;
			}
		}

		$classes = $this->getReference()->get('p_class');
		$r = array();

		foreach($classes as $class) {
			if(!isset($cache[$class])) continue;
			$r[] = (string) $cache[$class];
		}
		return implode(', ', $r);
	}

	public function getUser() {
		$username = $this->getReference()->get('p_user');
		if(module_exists('user') && $username) {
			$user = user_load(array(
				'name' => $username
			));

			if($user && user_access('access user profiles')) {
				$tag = Intra_View_Microformat_Tag::factory($username, 'a');
				$tag->setAttribute('href', url('user/'.$user->uid));
				$tag->setAttribute('title', t('View user profile.'));
				$tag->addClass('drupal-user');
			}
		}
		return (isset($tag)) ? $tag :   $this->tag('drupal-user', $username);
	}

	public function getNote() {
		$comment = $this->getReference()->get('p_rem');
		$comment = Intra_CMS()->filter($comment);
		$tag = Intra_View_Microformat_Tag::factory($comment, 'div', false);
		$tag->addClass('note');
		return $tag;
	}

	public function __toString() {
		$name = $this->getFn();
		$tag = 'span';

		if($this->getReference()->get('visible') != Intra_Object::VISIBLE) {
			$tag = 'del';
		}

		$id = get_class($this).'-'.$this->getReference()->get('p_fname').'-'.$this->getReference()->get('p_lname');
		$id = Intra_View_Microformat_Tag::cleanId($id);
		return '<'.$tag.' class="vcard person" id="'.$id.'" data-uid="'.$this->getUid().'">'.$name.'</'.$tag.'>';
	}
}
