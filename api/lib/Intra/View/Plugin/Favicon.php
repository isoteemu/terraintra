<?php

/**
 * Create image from favicon.
 * Uses google for that special secret sauce
 */
class Intra_View_Plugin_Favicon extends Intra_View_Plugin_Image {

	public function favicon( $url = null ) {
		$attributes =  array(
			'width'  => 16,
			'height' => 16
		);

		if($url == null) {
			$curl = '';
			if($curl = $this->getReference()->get('c_url')) {
				$curl = preg_replace('%^([a-z]+)://%', '', $curl);
				$url = sprintf('http://www.google.com/s2/favicons?domain=%s', rawurlencode($curl));
			}
		}
		if($url != null) {
			return $this->image($url, $attributes);
		}

		// Fallback - generate icon from logo
		$logo = $this->getLogo();
		try {
			$logo = $this->getLogo()->iconSmall();
		} catch( BadMethodCallException $e ) {
			// No such imagecache call defined :/
		}
		return $logo->setAttributes($attributes);

	}
}

