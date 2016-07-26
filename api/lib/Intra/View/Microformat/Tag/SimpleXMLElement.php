<?php
/**
 * Wrapper class for the simplexml class.
 * SimpleXML features some strange shit, so this class
 * tries to get around of them.
 *
 */
class Intra_View_Microformat_Tag_SimpleXMLElement implements RecursiveIterator, Countable, ArrayAccess {

	private $innerIterator;

	public final function __construct($data, $options=null, $ns=null, $is_prefix=null) {
		$this->innerIterator = new SimpleXMLElement($data, $options, $ns, $is_prefix);
	}

	public function addAttribute($name, $value, $namespace=null) {
		return $this->innerIterator->addAttribute($name, $value);
	}
	public function addChild($name, $value=null, $namespace=null) {
		return $this->innerIterator->addChild($name, $value, $namespace);
	}
	public function asXML(/* Filename not implemented */) {
		return $this->innerIterator->asXML();
	}
	public function attributes($ns=null, $is_prefix=null) {
		return $this->innerIterator->attributes($ns, $is_prefix);
	}
	public function children($ns=null, $is_prefix=false) {
		return $this->innerIterator->children($ns, $is_prefix);
	}
	public function getDocNamespaces($recursive=null) {
		return $this->innerIterator->getDocNamespaces($recursive);
	}
	public function getName() {
		return $this->innerIterator->getName();
	}
	public function getNamespaces($recursive=null) {
		return $this->innerIterator->getNamespaces($recursive);
	}
	public function registerXPathNamespace($prefix, $ns) {
		return $this->innerIterator->registerXPathNamespace($prefix, $ns);
	}
	public function xpath($path) {
		return $this->innerIterator->xpath($path);
	}


	public function count() {
		return $this->innerIterator->count();
	}

	public function current() {
		return $this->innerIterator->current();
	}
	public function getChildren() {
		return $this->innerIterator->getChildren();
	}
	public function hasChildren() {
		return $this->innerIterator->hasChildren();
	}
	public function key() {
		return $this->innerIterator->key();
	}
	public function next() {
		return $this->innerIterator->next();
	}
	public function rewind() {
		return $this->innerIterator->rewind();
	}
	public function valid() {
		return $this->innerIterator->valid();
	}

	public function offsetSet($offset, $value) {
		$this->innerIterator[$offset] = $value;
	}
	public function offsetExists($offset) {
		return isset($this->innerIterator[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->innerIterator[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->innerIterator[$offset]) ? $this->innerIterator[$offset] : null;
	}

	public function __call($func, $args) {
		return call_user_func_array(array(&$this->innerIterator, $func), $args);
	}
	public function __set($key, $value) {
		$this->innerIterator->{$key} = $value;
	}
	public function __get($key) {
		return $this->innerIterator->{$key};
	}

}