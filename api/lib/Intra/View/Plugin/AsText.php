<?php
/**
 * Convert view html into textual presentation.
 */

class Intra_View_Plugin_AsText extends Intra_View_Plugin {

	protected $wrapped;

	public function AsText($content=null) {
		if($content !== null)
			return $this->_convert($content);

		$clone = clone $this;
		return $clone;
	}

	public function __call($func, $args) {
		$this->wrapped = call_user_func_array(array($this->getView(), $func), $args);
		if(!isset($this->wrapped))
			$this->wrapped = '';
		return $this;
	}

	public function __toString() {
		if(isset($this->wrapped))
			$r = (string) $this->wrapped;
		else
			$r = (string) $this->getView();
		return (string) $this->_convert($r);
	}

	public function _convert($text) {

		@include_once('Horde/Text/Filter.php');
 		@include_once('Horde/Text/Filter/html2text.php');

		// People don't like hrefs' on links, so remove them.
		$text = preg_replace('/(<a[^>]*)( href="[^"]+")([^>]*>)/i', '\1\3', $text);

		// Try drupal
		if(module_exists('html2txt')) {
			$text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
			$converted = html2txt_convert($text, null, true);
			$text = $converted->text;
		} elseif(class_exists('Text_Filter_html2text')) {
			// Horde classes
			$text = Text_Filter::filter($text, 'html2text', array(
				'charset' => 'utf-8',
				'wrap' => false
			));
		} else {
			$text = strip_tags($text);
			$text = trim($text);
		}

		return $text;
	}
}
