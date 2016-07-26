<?php

/**
 * A "tag" presentation
 */
class Intra_View_Microformat_Tag_Tag extends Intra_View_Microformat_Tag {

	public static function factory($val='', $tagName = null, $escape = true, $class=null) {
		return parent::factory($val, $tagName, $escape, __CLASS__);
	}

	public function init() {
		$class = drupal_strtolower((string) $this[0]);
		$style = sprintf('color:#%s;', $this->colorCode((string) $this));

		$this->setAttribute('rel', 'tag');
		$this->addClass('tag-'.str_replace(array('][', '_', ' '), '-', $class));
		$this->setAttribute('style', $style);
	}

	/**
	 * Generate color from string.
	 * 
	 * @return
	 *   Hexadecimal color code
	 */
	protected function colorCode($string=true) {
		static $cache = array(), $text;
		if($string === true) 
			return $cache;

		$cid = drupal_strtolower($string);

		if($cache[$cid])
			return $cache[$cid];

		if(!isset($text)) {
			if(module_exists('color')) {
				global $user;
				$themes = list_themes();
				$theme = !empty($user->theme) && !empty($themes[$user->theme]->status) ? $user->theme : variable_get('theme_default', 'garland');
				$palette = color_get_palette($theme);

				$palette['text'] = ltrim($palette['text'], '#');
				$text = _color_unpack($palette['text']);
			} else {
				// Presume background is white
				$text = array(
					0 => 0,
					0 => 0,
					0 => 0
				);
			}
		}

		$chars = str_split($string);
		$i = 0;
		$r = array(
			0 => 0,
			1 => 0,
			2 => 0
		);

		foreach($chars as $char) {
			$r[$i++%3] += ord($char);
		}

		$cache[$cid] = '';
		foreach($r as $k => $n) {
			$mod = $n % 128;

			// Make sure that it's at least 64, and in maximum 190
			$c = max(64, $text[$k]);
			$c = min(190, $c);

			// Shift color.
			$mod = $c/2 + $mod;

			$cache[$cid] .= sprintf('%02X', $mod);
		}
		return $cache[$cid];
	}

	public function __toString() {
		$this[0] = t((string) $this[0]);
		return parent::__toString();
	}

}
