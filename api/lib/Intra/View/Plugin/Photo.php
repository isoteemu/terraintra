<?php


class Intra_View_Plugin_Photo extends Intra_View_Plugin {

	const DRUPAL = 1;
	const GOOGLE = 2;
	const FACEBOOK = 4;
	const GRAVATAR = 8;

	public $methods = array();

	public function init() {
		$cms = Intra_CMS()->getCMSType();

		if($cms == Intra_CMS::DRUPAL)
			$this->addMethod(array(&$this, '_getFromDrupal'), self::DRUPAL);

		if($cms == Intra_CMS::DRUPAL && module_exists('fbconnect'))
			$this->addMethod(array(&$this, 'getFromFacebook'), self::FACEBOOK);

		$this->addMethod(array(&$this, '_getFromGoogle'), self::GOOGLE);

		$this->addMethod(array($this, 'getGravatar'), self::GRAVATAR);
	}

	public function addMethod($callback, $idx=null) {
		if($idx===null) {
			$idx = count($this->methods);
		}
		$this->methods[$idx] = $callback;
	}

	public function photo() {
		$for = $this->methods;
		while($method = array_shift($for)) {
			$img = call_user_func($method);
			if($img)
				return $img;
		}
	}

	protected function _getFromDrupal() {
		$uid = $this->getReference()->get('p_user');

		if($uid) {
			$user = user_load(array('name' => $uid));
			if($user->picture) {
				return $this->image(file_create_path($user->picture));
			}
		}
	}


	/**
	 * Fetch profile picture from facebook.
	 * Uses facebook graph api to search for profile picture. Needs
	 * $this->facebook_token to be set to work.
	 */
	public function getFromFacebook() {

		$cid = 'Intra:Person:'.$this->getReference()->get('id').':photo:facebook';
		if($cache = cache_get($cid)) {
			$src = $cache->data;
		} else {

			$src = $this->throttleManager($this)->fetchFromFacebook();

			if($src !== null) {
				cache_set($cid, $src, 'cache', time()+30*24*60*60);
			}
		}

		$img = null;

		if($src) {
			$img = $this->image($src, array(
				'alt' => (string) $this->asText()->getFn()
			));
		}
		return $img;
	}

	/**
	 * Get facebook token.
	 * @TODO cache token.
	 */
	public function getFacebookToken() {
		if(empty($this->facebook_token)) {
			// DO MAGIC
		}

		return $this->facebook_token;
	}

	/**
	 * Actual fetching function.
	 * Use getFromFacebook() instead, as it handles caching and throttling.
	 * @see $this->getFromFacebook()
	 * @return Mixed
	 *   Image source uri from facebook graph, or false if contact was not found.
	 */
	public function fetchFromFacebook() {
		if(!module_exists('fbconnect')) {
			Intra_CMS()->dfb('Facebook Connect module is not enabled', 'warning');
			return null;
		}

		// Check if connected
		if(!fbconnect_get_fbuid(true)) {
			return null;
		}

		$emails = $this->getReference()->getEmails()->each()->get('pe_email');
		foreach($emails as $email) {
			dfb($email, 'facebook photo search');

			$id = $this->_searchFacebookProfile($email);


			if($id && is_numeric($id)) {
				$http_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
				#$size = variable_get('fbconnect_pic_size', 'normal');
				$size = 'square';
				return $http_protocol.'://graph.facebook.com/'.$id.'/picture?type='.urlencode($size);
			}
		}

		return false;
	}

	/**
	 * Get photo from gravatar
	 * @see http://en.gravatar.com/site/implement/images/
	 */
	public function getGravatar() {
		$email = $this->getReference()->get('p_email');
		$email = trim($email);
		if(!$email) return false;

		$email = strtolower($email);
		$hash = md5($email);

		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$host = 'https://secure.gravatar.com';
		} else {
			$host = 'http://www.gravatar.com';
		}

		$fallback = drupal_get_path( 'module', 'intra_api').'/image/im-user.png';
		$fallback = url($fallback, array('absolute' => true));

		$src = sprintf('%s/avatar/%s.jpg?s=64&d=%s', $host, $hash, urlencode($fallback));

		return $this->image($src, array(
			'alt' => $email,
			'width' => 64,
			'height' => 64
		));
	}

	/**
	 * Search from facebook with email.
	 */
	protected function _searchFacebookProfile($email) {
		$session = facebook_client()->getSession();

		$q = 'https://graph.facebook.com/search?type=user&access_token='.$session['access_token'].'&q='.urlencode($email);

		$http_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
		$data = drupal_http_request($q, array('Referer' => $http_protocol.'://'.$_SERVER['HTTP_HOST']));

		if($data->code != 200) {
			return false;
		}

		$r = json_decode($data->data);

		if(isset($r->data[0]->id)) {
			return $r->data[0]->id;
		}

		return false;
	}

	/**
	 * Retrieve contact photo from google.
	 */
	protected function _getFromGoogle() {
		$cid = 'Intra:Person:'.$this->getReference()->get('id').':photo:google';
		if($cache = cache_get($cid)) {
			$data = $cache->data;
			$src = $data->tbUrl;
		} else {

			$data = $this->throttleManager($this)->getFromGoogleReal();
			if($data !== null) {
				cache_set($cid, $data, 'cache', time()+24*60*60);
				$src = $data->tbUrl;
			}
		}

		if($src) {
			$img = $this->image($src);
			if(is_object($data)) {
				$img->setAttributes(array(
					'width' => $data->tbWidth,
					'height' => $data->tbHeight,
					'alt' => (string) $this->getFn()
				));
			}
		}
		return $img;
	}

	public function getFromGoogleReal() {
		$emails = $this->getReference()->getEmails()->each()->get('pe_email')->getChildren();

		$attr = array(
			'imgtype' => 'face',
			'v' => '1.0',
			'key' => GOOGLE_APPKEY,
			'oe' => 'utf-8',
			'q' => sprintf('(%1$s) OR ("%2$s %3$s" %4$s)',
				'"'.implode('" OR "', $emails).'"',
				$this->getReference()->get('p_fname'),
				$this->getReference()->get('p_lname'),
				$this->getReference()->getCompany()->get('c_cname')
			)
		);

		$imgsearch = 'http://ajax.googleapis.com/ajax/services/search/images?';

		Intra_CMS()->dfb($attr, 'Fething contact photo from google');

		foreach($attr as $key => $val) {
			$imgsearch .= sprintf('%s=%s&', urlencode($key), urlencode($val));
		}

		$data = drupal_http_request($imgsearch, array('Referer' => 'http://'.$_SERVER['HTTP_HOST']));

		if($data->code != 200) {
			return false;
		}

		$r = json_decode($data->data);
		$data = false;
		if(count($r->responseData->results)) {
			$data = $r->responseData->results[0];
		}

		return $data;
	}
}
