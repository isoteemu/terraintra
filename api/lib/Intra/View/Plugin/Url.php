<?php
/**
 * Create url.
 * @todo Works currently only on Drupal, and depends on intra_api -module.
 */
class Intra_View_Plugin_Url extends Intra_View_Plugin {

	public function url($path='') {
		if($path && $path[0] != '/') $path = '/'.$path;
		return url(intra_api_url($this->getReference()).$path);
	}
}
