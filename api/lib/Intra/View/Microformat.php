<?php

/**
 * Intra view wrapper for HTML presentation with semantic markup.
 * @ingroup TerraIntra_View
 */
class Intra_View_Microformat extends Intra_View {

	protected $_handler;

	// Accessor functions
	protected $_accessors = array();

	// tag nodes associated with this view
	protected $tree = array();

	protected function init() {
		if(!$this->tree) {
			$this->tree = Intra_View_Microformat_Tag::factory('', 'div');
		}
	}

	/**
	 * Create simple span tag.
	 * @param $class String
	 *   Tag classes to set
	 * @param $value
	 *   Tag value to set
	 * @param $escape Bool
	 *   Should value be escaped. @see Intra_View_Microformat_Tag::factory()
	 * @return Intra_View_Microformat_Tag
	 */
	public function tag($class, $value='', $escape=true) {
		$tag = Intra_View_Microformat_Tag::factory($value, 'span', false);
		$tag->addClass($class);
		return $tag;
	}

	/**
	 * Wrapper for Intra_Object::get().
	 * If $this->_handler can provide wrapper for $attr,
	 * use it. If not, return escaped value for HTML page
	 * inclusion.
	 * @return String
	 */
	public function get($attr) {
		if(isset($this->_accessors[$attr])) {
			try {
				$tag = call_user_func_array(array(&$this, $this->_accessors[$attr]), $attr);
			} catch(Exception $e) {
				Intra_CMS()->dfb($e);
				$tag = $this->tag('ui-state-error', t('Failed to retrieve %attr', array('%attr' => $attr)));
				$tag->setAttribute('title', $e->getMessage());
			}
		} else {
			$args = func_get_args();
			$val = call_user_func_array(array($this->getReference(), 'get'), $args);

			// Add in view wrapper
			if($val instanceOf Intra_Object) {
				try {
					$view = Intra_View::factory($val);
					return $view;
				} catch(UnexpectedValueException $e) {
					Intra_CMS()->dfb($e);
				}
			}

			$tag =  Intra_View_Microformat_Tag::factory($val, 'span');
		}
		return $tag;
	}

	public function getUid() {
		return get_class($this->getReference()).'-'.$this->getReference()->get('id');
	}

}
