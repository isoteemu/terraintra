<?php

class Intra_View_Plugin_Image_Tag extends Intra_View_Microformat_Tag {

	/**
	 * Fallback presets. Thease don't actually scale image, just provides
	 * information on which size should icons be.
	 * 
	 * Should be an object property, but simplexml element is an odd beast.
	 * @return Array
	 */
	protected function _getPresets() {
		return array(
			'iconSmall' => array(
				'width' => 16,
				'height' => 16
			),
			'iconMedium' => array(
				'width' => 22,
				'height' => 22
			),
			'iconLarge' => array(
				'width' => 32,
				'height' => 32
			),
			'iconHuge' => array(
				'width' => 48,
				'height' => 48
			),
			'thumbnail' => array(
				'width' => 64,
				'height' => 64
			)
		);
	}

	/**
	 * magic for different imagecache presents. This allows one to call eg.
	 * $Intra_View_Plugin_Image->thumbnail(), which then generates image
	 * by using imagecache "thumbnail" preset.
	 * Preset which to use is defined on $this->_preset variable.
	 */
	public function __call($func, $args) {

		$preset = false;
		if(module_exists('imagecache')) {
			$presets = module_invoke('imagecache', 'presets');
			foreach($presets as $set) {
				if($set['presetname'] == $func) {
					$preset = $set;
					break;
				}
			}
		}

		$this['data-container-class'] = $func;

		if($preset && $this->_isLocalSrc()) {
			$this->_imageCache($preset);
		} else {
			$fallback = $this->_getPresets();

			if(isset($fallback[$func])) {
				$this['src'] = url((string) $this['src']);
				$this->_remoteImage($fallback[$func], $func);

				dfb('You\'re missing imagecache preset "'.$func.'". Attributes should be on line with:'.print_r($fallback[$func], 1));
			} else {
				throw new BadMethodCallException('No such imagecache preset '.$func);
			}
		}

		return $this;
	}

	/**
	 * Test if image is local
	 */
	protected function _isLocalSrc() {
		return (file_exists((string)$this['src'])) ? true : false;
	}

	/**
	 * Convert image using imageCache api
	 */
	protected function _imageCache($preset) {
		$this->addClass('imagecache')->addClass('imagecache-'.$preset['presetname']);

		$path = imagecache_create_path($preset['presetname'], $this['src']);

		if(!file_exists($path)) {
			/// HACK: Imagecache fails on first run to return correct image size.
			/// Get size from preset
			foreach($preset['actions'] as $act) {
				if(isset($act['data']['width']))
					$this['width'] = _imagecache_percent_filter($act['data']['width'], $this['width']);
				if(isset($act['data']['height']))
					$this['height'] = _imagecache_percent_filter($act['data']['height'], $this['height']);
			}
		} elseif($image = image_get_info($path)) {
			$this['width'] = $image['width'];
			$this['height'] = $image['height'];
		}
		$this['src'] = imagecache_create_url($preset['presetname'], $this['src']);
	}

	protected function _remoteImage($attr, $type) {

		foreach(array('width',  'height') as $key) {
			if($attr[$key]) {
				$this["data-container-$key"] = $attr[$key];
			}
		}

		// Image resizing in aspect
		if(isset($this['width']) && isset($this['height']) && isset($this['data-container-width']) &&  isset($this['data-container-height'])) {
			$imgAspect = (int) $this['width'] / (int) $this['height'];
			$contAspect = (int)$this['data-container-width'] / (int) $this['data-container-height'];

			if($imgAspect != $contAspect) {
				if($imgScale > $contScale) {
					// Is wider
					$ratio = (int) $this['width'] / (int) $this['data-container-width'];
					$attr['width'] = (int) $this['data-container-width'];
					$attr['height'] = round((int) $this['height'] / $ratio);
				} else {
					// Is taller
					$ratio = (int) $this['height'] / (int) $this['data-container-height'];
					$attr['height'] = (int) $this['data-container-height'];
					$attr['width'] = round((int) $this['width'] / $ratio);
				}
			}
		}

		$this->setAttributes($attr);
	}

	/**
	 * Returns rounded corners image, if appropriate
	 * image scale is found.
	 */
	protected function _roundedCorners() {
		// Image scale (px*px) => sprite offset
		$scales = array(
			16 => 0,
			22 => 16,
			32 => 38, // 22+16
			48 => 70, // 16+22+32
			64 => 118
		);

		if(isset($this['data-container-width']) && isset($this['data-container-height'])) {
			$w = (int)$this['data-container-width'];
			$h = (int)$this['data-container-height'];
		} else {
			$w = (int)$this['width'];
			$h = (int)$this['height'];
		}

		if($w <> $h)
			return '';

		$src = drupal_get_path('module', 'intra_api').'/image/corner-sprite.png';

		if(isset($scales[$h])) {
			return '<img
				unselectable="on"
				class="roundcorner-sprite"
				src="'.url($src).'"
				alt=""
				style="position:absolute;top:-'.$scales[$h].'px;left:0;"
			/>';
		}

	}

	/**
	 * Generate pretty container with image on it
	 * @return String
	 *   Image tag
	 */
	public function __toString() {

		if(isset($this['data-container-width']) AND isset($this['width']) && (int) $this['width'] > (int) $this['data-container-width']) {
			// Adjust image to center
			$this['style'] = 'margin-left:'.round(($this['data-container-width']-$this['data-container-height']) / 2).'px';
		}

		$sprite = $this->_roundedCorners();

		try {
			$img = parent::__toString();
		} catch(Exception $e) {
			die($e->getMessage());
		}

		$o   = array();
		$o[] = '<span class="View-Image-Container '.$this['data-container-class'].'" style="width:'.$this['data-container-width'].'px;height:'.$this['data-container-height'].'px;">';
		$o[] = '  '.$img;
		$o[] = '  '.$sprite; // Add corner sprite, if found.
		$o[] = '</span>';

		return implode("\n", $o);
	}
}
