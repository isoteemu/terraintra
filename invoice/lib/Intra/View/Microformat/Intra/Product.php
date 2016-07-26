<?php

class Intra_View_Microformat_Intra_Product extends Intra_View_Microformat {

	protected function init() {
		$this->_accessors += array(
			'pr_id'		=> 'getProduct',
			'se_c_id'	=> 'getEndCustomer',
			'se_serial'	=> 'getSerial',
			'se_type'	=> 'getType',
			'se_rownr'	=> 'getRowNr'
		);
	}

	public function getProduct() {

		try {
			$product = Product_Map::load($this->getReference()->get('pr_id'));

			$tag = Intra_View_Microformat_Tag_Tag::factory($product->get('pr_name'));
			$tag->addClass('intra_product product-'.$product->get('pr_id'));
			if($rem = $product->get('pr_rem')) {
				$tag->setAttribute('title', check_plain($rem));
			}
		} catch(RuntimeException $e) {
			Intra_CMS()->dfb($e);
			$tag = $this->tag('ui-state-error', t('Product?'));
			$tag->addClass('product-'.$this->getReference()->get('pr_id'));
		}
		return $tag;

	}

	public function getEndCustomer() {
		$c_id = $this->getReference()->get('se_c_id');
		if($c_id) {
			$company = Company::load($c_id);
			return Intra_View::factory($company);
		}
	}

	public function getSerial() {
		return $this->tag('se_serial', $this->getReference()->get('se_serial'));
	}

	public function getType() {
		$code = Codes::getCode('SE_TYPE', $this->getReference()->get('se_type'));
		if($code) {
			$name = $code->get('cd_name');
			$class = 'serial-type-'.$code->get('cd_value');
		} else {
			$name = 'Serial type missing';
			$class = 'ui-state-error';
		}
		$tag = Intra_View_Microformat_Tag_Tag::factory($name);
		$tag->addClass($class);
		return $tag;
	}

	public function getRowNr() {
		$nr = $this->getReference()->get('se_rownr');
		return $this->tag('se_rownr', $nr);
	}

	public function getNote() {
		$comment = $this->getReference()->get('se_rem');
		$comment = Intra_CMS()->filter($comment);
		$tag = Intra_View_Microformat_Tag::factory($comment, 'div', false);
		$tag->addClass('note');
		return $tag;
	}
}
