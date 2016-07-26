<?php

class Intra_View_Microformat_Company extends Intra_View_Microformat {

	protected $_accessors = array(
		'c_cname'		=> 'getFnOrg',
		'c_street'		=> 'getStreet',
		'c_box'			=> 'getBox',
		'c_city'		=> 'getLocality',
		'c_zip'			=> 'getZip',
		'c_country'		=> 'getCountry',
		'c_logo'		=> 'getLogo',
		'c_location'	=> 'getLocation',
		'c_url'			=> 'getUrl',
		'c_email'		=> 'getEmail',
		'c_class'		=> 'getClasses',
		'c_type'		=> 'getType',
		'c_regdate'		=> 'getRegDate',
		'c_rem'			=> 'getNote',
		'prospect_by'	=> 'getProspectBy'
	);

	public function init() {
		parent::init();
		$this->tree->addClass('vcard');
	}

	public function getFnOrg() {

		$realname = $name = $this->getReference()->get('c_cname');
		if(module_exists('transliteration')) {
			$lang = null;

			if(module_exists('countries_api')) {
				$c_country = $this->getReference()->get('c_country');
				if($_country = _countries_api_iso_get_country($c_country, 'printable_name'))
					$lang = $_country['iso2'];
			}

			$name = transliteration_get($name, '?', $lang);
		}

		if(function_exists('intra_api_url') && $url = intra_api_url($this->getReference())) {
			$tag = Intra_View_Microformat_Tag::factory($realname, 'a');
			$tag->setAttribute('href', url($url));

			if(module_exists('votingapi')) {
				$tag->setAttribute('ping', url($url.'/ping'));
			}
			$tag->addClass('url');
		} elseif($url = $this->getReference()->get('c_url')) {
			$tag = Intra_View_Microformat_Tag::factory($realname, 'a');
			$tag->setAttribute('href', $url);

			$tag->addClass('url');
		} else {
			$tag = Intra_View_Microformat_Tag::factory($realname, 'span');
		}

		if($realname != $name) {
			$tag->setAttribute('title', $name);
		}
		$tag->addClass('fn org');
		return $tag;
	}

	public function getStreet() {
		return $this->tag('street-address', $this->getReference()->get('c_street'));
	}

	public function getBox() {
		return $this->tag('post-office-box', $this->getReference()->get('c_box'));
	}

	public function getZip() {
		return $this->tag('postal-code', $this->getReference()->get('c_zip'));
	}

	public function getLocality() {
		$realname = $name = $this->getReference()->get('c_city');
		if(module_exists('transliteration')) {
			$name = transliteration_get($name);
		}

		$tag = $this->tag('locality', $realname);
		if($name != $realname) {
			$tag->setAttribute('title', $name);
		}

		return $tag;
	}

	public function getCountry() {
		$country = $this->getReference()->get('c_country');

		$tag = Intra_View_Microformat_Tag_Country::factory($country);
		$tag->addClass('country-name');

		return $tag;
	}

	public function getLogo() {
		if(!$this->getReference()->get('c_logo') && $this->getReference()->get('c_url') && $this->preparePlugin('WebpageLogo')) {
			$img = $this->WebpageLogo($this->getReference()->get('c_url'));
		}

		if(!$img) {
			$src = $this->getLogoSrc();
			$img = $this->image($src);
		}

		$img->addClass('logo');
		return $img;
	}

	public function getLogoSrc() {
		$src = $this->getReference()->get('c_logo');
		if(!$src && $this->getReference()->get('c_url') && $this->preparePlugin('WebpageLogo')) {

		}

		// Fallback
		if(!$src) {
			$type = $this->getReference()->get('c_type');
			// Stub types
			switch($type) {
				case '0' : /// Admin company
					$src = drupal_get_path('module', 'intra_api').'/image/company-admin.png';
					break;
				case '1' : /// Distributor
					$src = drupal_get_path('module', 'intra_api').'/image/company-distributor.png';
					break;
				case '7' : /// Prospect
					$src = drupal_get_path('module', 'intra_api').'/image/company-prospect.png';
					break;
				default :
					$src = drupal_get_path('module', 'intra_api').'/image/company.png';
					break;

			}
			variable_get('intra_company_logo_type_'.$type, $src);
		}
		return $src;
	}

	/**
	 * Get geo location (wgs84)
	 */
	public function getLocation() {

		try {
			$geo = $this->getReference()->get('c_location');

			if(!$geo)
				$geo = $this->throttleManager()->geoCode();
			if($geo) {
				return Intra_View_Microformat_Tag_Location::factory($geo);
			}
		} catch(Exception $e) {
			dfb($e->getMessage());
		}
		return '';
	}

	public function geoCode() {
		$geo = intra_api_geocode($this->getReference());
		return $geo;
	}

	/**
	 * Convert company codes into human readable values
	 */
	public function getClasses() {
		static $cache;
		if(!isset($cache)) {
			$search = module_exists('intra_search_company');
			$tagName = ($search) ? 'a' : 'span';

			foreach(Codes::map('C_CLASS') as $code) {
				$tag = Intra_View_Microformat_Tag_Tag::factory(t($code->get('cd_name')), $tagName);
				$tag->addClass('company-class-'.$code->get('cd_value'));

				if($search)
					$tag->setAttribute('href', url('search/intra_search_company/c_class:'.urlencode($code->get('cd_value'))));

				$cache[$code->get('cd_value')] = $tag;
			}
		}

		$classes = $this->getReference()->get('c_class');
		$r = array();

		foreach($classes as $class) {
			if(!isset($cache[$class])) continue;
			$r[] = $cache[$class]->__toString();
		}
		return implode(', ', $r);
	}

	public function getType() {
		$code = Codes::getCode('C_TYPE', $this->getReference()->get('c_type'));
		$tag = $this->tag('company-type-'.$code->get('cd_value'), t($code->get('cd_name')));
		return $tag;
	}

	public function getRegDate() {
		return Intra_View_Microformat_Tag_Time::factory($this->getReference()->get('c_regdate'))->addClass('c_regdate');
	}

	/**
	 * Generate linked url
	 */
	public function getUrl() {
		$tag = '';
		if($url = $this->getReference()->get('c_url')) {
			$fav = $this->favicon()->__toString();
			$_url = check_url($url);
			$tag = Intra_View_Microformat_Tag::factory($fav.$_url, 'a', false);
			$tag->addClass('website')->addAttribute('href', $url);
		}
		return $tag;
	}

	/**
	 * Returns company official email address.
	 * @todo should look from primary contact too.
	 */
	public function getEmail() {
		$email = $this->getReference()->get('c_email');
		$email = check_plain($email);
		if($email) {
			$tag = Intra_View_Microformat_Tag::factory($email, 'a');
			$tag->addAttribute('href', 'mailto:'.$email);
			$tag->addClass('email');
			return $tag;
		}
	}

	/**
	 * Returns stripped version of remarks
	 */
	public function getNote() {
		$comment = $this->getReference()->get('c_rem');
		$comment = Intra_CMS()->filter($comment);
		$comment = node_teaser($comment);

		$tag = Intra_View_Microformat_Tag::factory($comment, 'div', false);
		$tag->addClass('note');
		return $tag;
	}

	public function getRemarks() {
		$comment = $this->getReference()->get('c_rem');
		$comment = Intra_CMS()->filter($comment);

		$tag = Intra_View_Microformat_Tag::factory($comment, 'div', false);
		$tag->addClass('note');
		return $tag;
	}

	public function getProspectBy() {
		try {
			$person = Person::load($this->getReference()->get('prospect_by'));
			return Intra_View::factory($person);
		} catch(Exception $e)  {}
	}

	/**
	 * Get contact person
	 * @return Intra_View_Microformat_Person
	 */
	public function getContact() {
		return $this->getPerson('Contact');
	}

	public function getAccountist() {
		return $this->getPerson('Account');
	}

	public function getManager() {
		return $this->getPerson('Manager');
	}

	/**
	 * Get company person by type.
	 * @return Intra_View_Microformat_Person
	 */
	public function getPerson($type) {
		$filter = new Intra_Filter();
		$filter->whereRecordlist('p_class',$type);
		$contacts = $filter->filterItems($this->getReference()->people());
		if($contacts->count()) {
			$contact = $contacts->current();
			return Intra_View::factory($contact);
		}
	}

	public function __toString() {
		$class = 'vcard company';
		$tag   = 'span';
		if($this->getReference()->get('visible') != Intra_Object::VISIBLE) {
			$tag = 'del';
		}

		return '<'.$tag.' class="'.$class.'" data-uid="'.$this->getUid().'">'.
			$this->getFnOrg()->__toString().
			'</'.$tag.'>';
	}
}
