<?php
/**
 * Data collection which contains relation to object.
 */

class Intra_Datacollection extends Intra_Helper implements ArrayAccess {

	protected $reference;

	public function setReference(&$object) {
		$this->reference =& $object;
		return $this;
	}

	public function &getReference() {
		if(!isset($this->reference))
			throw new Exception('Missing reference object');

		return $this->reference;
	}


	public function implodeBy($property) {
		$typed = array();
		foreach($this->children as $obj) {
			$txt = (string) $obj->get($property);
			// Commas and quotes in tag names are special cases, so encode 'em.
			if (strpos($txt, ',') !== FALSE || strpos($txt, '"') !== FALSE) {
				$txt = '"'. str_replace('"', '""',$txt) .'"';
			}
			$typed[] = $txt;
		}

		return implode(', ', $typed);
	}

	public static function explode($typed) {

		$regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
		preg_match_all($regexp, (string) $typed, $matches);
		$typed_tags = array_unique($matches[1]);

		$items = array();
		foreach ($typed_tags as $tag) {
			// If a user has escaped a term (to demonstrate that it is a group,
			// or includes a comma or quote character), we remove the escape
			// formatting so to save the term into the database as the user intends.
			$tag = trim(str_replace('""', '"', preg_replace('/^"(.*)"$/', '\1', $tag)));
			if (!empty($tag)) {
				$items[] = $tag;
			}
		}
		return $items;
	}

	public function offsetSet($offset, $value) {
		$relKey = $this->getReference()->getPrimaryKey();
		if(!$value->get($relKey)) {
			$value->set($relKey, $this->getReference()->get($relKey));
		}
		if(empty($offset))
			$offset = $value->get('id');

		$this->children[$offset] = $value;
	}

	public function offsetExists($offset) {
		return isset($this->children[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->children[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->children[$offset]) ? $this->children[$offset] : null;
	}
}
