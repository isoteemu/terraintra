<?php

/**
 * SimpleXML wrapper for to be used as magical Microformat tag.
 * @see http://www.php.net/simplexml
 */
class Intra_View_Microformat_Tag extends Intra_View_Microformat_Tag_SimpleXMLElement {
	public static $tagName = 'span';

	/**
	 * SimpleXMLElement->__construct() is final, so use a hack-is approach.
	 *
	 * @param $val String
	 *   Inner-value for tag. Is accessible thru $tag[0]
	 * @param $tagName String
	 *   Name of element.
	 * @param $escape Bool
	 *   Escape inner string $val, or leave (and parse as SimpleXML tag-tree).
	 * @param $class String
	 *   Class to use for construction, as we support PHP 5.2. An Hack-ish approach
	 *   before PHP 5.3 lazy static binding.
	 * @return Instance of $class, if given. Else instance of Intra_View_Microformat_Tag
     */
	public static function factory($val='', $tagName = null, $escape = true, $class=null) {
		if($tagName == null)
			$tagName = self::$tagName;

		if($escape) {
			$val = check_plain($val);
		} if(!isset($val) || empty($val)) {
			$val = '<![CDATA[]]>';
		}

		if($class == null)
			$class = __CLASS__;

		$tag = sprintf('<%1$s>%2$s</%1$s>', $tagName, $val);

		if(class_exists('tidy') && false) {
			$tidy = tidy_parse_string($tag,  array(
				'input-xml' => true,
				'output-xml' => true,
				'numeric-entities' => true,
				'indent' => false,
			), 'utf8');
			$tidy->cleanRepair();
			$tag = (string) $tidy->value;
		} else {
			if(module_exists('filter')) {
				// htmlcorrector doesn't work with multi-line tags.
				$tag = str_replace("\n", ' ', $tag);
				$tag = preg_replace('/\s{2,}/', ' ', $tag);
				$tag = _filter_htmlcorrector($tag);
			}
			if(function_exists('decode_entities')) {
				$tag = decode_entities($tag);
			} else {
				$tag = html_entity_decode($tag, ENT_COMPAT, 'utf-8');
			}
			$tag = str_replace('&', '&#38;', $tag);
		}

		$r = new $class($tag, LIBXML_COMPACT | LIBXML_NOWARNING | LIBXML_NOENT);
		if(method_exists($r, 'init')) {
			$r->init();
		}

		return $r;
	}

	public function addChild($name, $value=null, $namespace=null) {
		if($name instanceof self) {
			$r = parent::addChild($name->getName(), $name[0]);
			foreach($name->attributes() as $attrKey => $attrValue) {
				$r->addAttribute($attrKey, $attrValue);
			}
			foreach($name->children() as $child) {
				$r->addChild($child);
			}
			return $r;
		} else {
			return parent::addChild($name, $value, $namespace);
		}

	}

	public function addClass($class) {
		$attributes = $this->attributes();
		if($this['class'])
			$this['class'] .= ' '.$class;
		else
			$this['class'] = $class;

		return $this;
	}

	public function &setAttributes(array $attributes) {

		foreach($attributes as $key => $val) {
			$this->setAttribute($key, $val);
		}
		return $this;
	}

	public function &setAttribute($key, $val) {
		switch($key) {
			case 'id' :
				$val = $this->cleanId($val);
				break;
		}

		if(isset($this[$key]))
			$this[$key] = $val;
		else
			$this->addAttribute($key, $val);
		return $this;
	}

	public function removeClass($class) {
		$this['class'] = preg_replace('(^| )'.preg_quote($class).'( |$)', '', $this['class']);
		return $this;
	}

	/**
	 * Clean tag ID.
	 * Removes invalid characters from $id, and prevents
	 * double ID's.
	 * @param $id String
	 */
	public static function cleanId($id) {
		static $seen;
		$id = str_replace(array('][', '_', ' '), '-', $id);
		if(isset($seen[$id]))
			$id .= '-'.$seen[$id]++;
		else
			$seen[$id] = 0;
		return $id;
	}

	public function __toString() {
		$xml = $this->asXML();
		// Remove <xml> header and unnecessary stuff
		$xml = str_replace(array('<?xml version="1.0"?>'), '', $xml);
		$xml = str_replace("\n", ' ', $xml);
		$xml = preg_replace('/\s{2,}/', ' ', $xml);
		$xml = trim($xml);

		Intra_CMS()->filter($xml, Intra_CMS_Common::FORMAT_HTML);

		return (string) $xml;
	}
}
