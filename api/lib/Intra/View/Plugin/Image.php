<?php
/**
 * Image plugin
 */
class Intra_View_Plugin_Image extends Intra_View_Plugin {

	public function image( $src, array $attributes = array()) {

		$image = new Intra_View_Plugin_Image_Tag('<img />');

		if($meta = @image_get_info($src)) {
			$attributes['width'] = (isset($attributes['width'])) ? $attributes['width'] : $meta['width'];
			$attributes['height'] = (isset($attributes['height'])) ? $attributes['height'] : $meta['height'];
		}

		$attributes['src'] = $src;
		$image->setAttributes($attributes);

		return $image;

	}
}