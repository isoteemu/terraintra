<?php

abstract Intra_View_Microformat {

	protected $id = 0;

	protected $_reference;

	protected $_accessors = array();

	protected $_nodes = array();
	// Parent node ID
	protected $_parent;

	// Own type and value
	protected $_type  = '';
	protected $_value = '';

	protected $_rendered = false;

	public function __call($func, $args) {
		if($func == 'get' && isset($this->_accessors[$args[0]])) {
			$val = call_user_func_array(array(&$this->_reference,$func), $args);

			$parent        = clone $this;

			$this->_type   = $args[0];
			$this->_parent = $this->id;

			$this->id++;
			$this->nodes[$this->id] =& $this;

			return $this->nodes[$this->id];
		}
		return call_user_func_array(array(&$this->_reference,$func), $args);
	}

	public function __construct(Intra_Object &$reference) {
		$this->_reference =& $reference;
	}

	public function attributes(Array $attributes) {
		foreach($attributes as $name => $value) {
			$this->setAttribute($name, $value);
		}
	}

	public function setAttribute($name, $value) {

	}

	/**
	 * Find parent node, defined by type
	 */
	protected function findParentByType($type) {
		if($this->_type == $type)
			return $this;

		if($this->_parent)
			return $this->_nodes[$this->_parent]->findParentByType($type);

		return false;
	}

	public function isRendered() {
		return ($this->_rendered) ? true : false;
	}

	/**
	 * Render self, and all unrendered parents.
	 */
	public function __toString() {
		$this->_rendered = true;
		return '<span class="'.$this->_type.'">'.$this->_value.'</span>';
	}
}

class Intra_View_Microformat_Company extends Intra_View_Microformat {

	protected $_accessors = array(
		'c_cname' => 'getName',
		'c_url'   => 'getUrl',
		'address' => 'getAddress',
		'c_street'=> 'getStreetAddress'
	);

	public function getName() {
		return '<span class="fn org">'..'</span>';
	}

	public function getUrl() {
		return '<a href="">'..'</a>';
	}

	public function getAddress() {
		$o   = array();
		$o[] = '<div class="adr">';
		$o[] = '<div>';
		$o[] = $this->get('c_street');
		$o[] = '</div><div>';
		$o[] = $this->get('')
		$o[] = '</div>';
		return join("\n", $o);
	}
}