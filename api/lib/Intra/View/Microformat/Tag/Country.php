<?php
/**
 * Add country flags and other info into a tag when rendering into a stirng
 */
class Intra_View_Microformat_Tag_Country extends Intra_View_Microformat_Tag {

	public static function factory($val='', $tagName = 'a', $escape = true, $class=__CLASS__) {

		$tag = parent::factory($val, $tagName, $escape, $class);
		if($tagName == 'a' && module_exists('intra_search_company')) {
			$tag['href'] = url('search/intra_search_company/c_country:'.urlencode($val));
		}

		return $tag;
	}

	public function __toString() {

		if(module_exists('countries_api')) {
			
			if($_country = _countries_api_iso_get_country((string) $this[0], 'printable_name')) {
				if(!$this['title'])
					$this['title'] = (string) $this[0];

				$this[0] = t($_country['printable_name']);
			}
		}

		drupal_add_css(drupal_get_path('module', 'intra_api').'/css/countryselect.css');

		$me = parent::__toString();
		return $me;
	}
}