<?php
/**
 * This is an extender to hkit class, to prefer drupal functions.
 */
@include_once(dirname(__FILE__).'/../../external/hkit/hkit.class.php');

class Drupal_HKit extends hKit {
	
	public $tidy_mode = 'php';

	protected function loadURL($url) {
		$this->url	= $url;

		if ($this->tidy_mode == 'proxy' && $this->tidy_proxy != ''){
			$url	= $this->tidy_proxy . urlencode($url);
		}

		$request = drupal_http_request($url);

		if($request->code != '200' && $request->redirect_code != '200') {
			throw new RuntimeException('Request for url '.$url.' returned invalid code', $request->code);
			return false;
		}

		return $data;
	}
}
