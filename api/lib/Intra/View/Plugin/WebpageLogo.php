<?php

/**
 * Tries to fetch logo image from webpage.
 * @TODO, move fething into separate logic
 */
class Intra_View_Plugin_WebpageLogo extends Intra_View_Plugin_Image {

	/**
	 * Regular expression to detect logo from web page
	 */
	public $logoRegex = '/<img[^>]* src=("[^"]*[^"]+"|\'[^\']*[^\']+\'|[^\s]*[^\s]+\s+)[^>]*>/i';

	/**
	 * Allowed image extensions
	 */
	public $image = array(
		'image/gif' => 'gif',
		'image/jpg' => 'jpg',
		'image/jpeg' => 'jpeg',
		'image/png' => 'png'
	);

	/**
	 * HTML mime types.
	 * Extensions aren't used.
	 */
	public $html = array(
		'text/html' => 'html',
		'text/xml' => 'xml',
		'application/xml' => 'xml',
		'application/xhtml+xml' => 'xhtml'
	);

	/**
	 * Logo sizes.
	 * @var $maxSize
	 *   Logo size maximum dimenssions.
	 * @var $minSize
	 *   Minimum logo dimenssions.
	 */
	public $maxSize = '1600x1200';
	public $minSize = '24x24';

	protected $cachePrefix = __CLASS__;
	protected $cacheTTL = 2592000; // 1 month

	/**
	 * Image storage path
	 */
	public $filepath = 'intra_company_webpagelogos';

	public function init() {
		$this->maxSize = variable_get('upload_max_resolution', $this->maxSize);
	}

	/**
	 * Retrieve logo from webpage.
	 * @param $url String
	 *   Url to search logo
	 * @return Intra_View_Plugin_Image
	 */
	public function WebpageLogo($url) {
		$url = trim($url);
		if(!check_url($url, true)) {
			throw new UnexpectedValueException('Invalid url: '.$url);
		}
		$file = $this->file($url);
		$path = $this->checkFile($file);

		if($path) {
			return $this->image($path);
		}

		// If image has been found and exists, of seeked but not found, return cache
		$cid = $this->cachePrefix.':'.$file;
		$cache = cache_get($cid);

		if($cache AND ( $cache->data == false || file_exists($cache->data))) {
			$image = $this->data;
		} else {
			$image = $this->throttleManager()->invoke(array(&$this, 'retrieveImage'), $url, $file);

			if($image !== null)
				cache_set($cid, $image, 'cache', time()+$this->cacheTTL);
		}

		if($image)
			return $this->image($image);
		else
			return $this->image('http://images.websnapr.com/?url='.urldecode($url), array(
				'width' => 202,
				'height' => 152
			));
;
	}

	public function retrieveImage($url, $file) {
		$url = trim($url,'/');
		$url = (preg_match('/^(http|https):\/\//i', $url)) ? $url : 'http://'.$url;

		try {
			$data = $this->request($url, $this->html);
			if(!$data) return false;

			$baseurl = (isset($data->redirect_url)) ? $data->redirect_url : $url;

			if($imageUrl = $this->seekImageUrl($data->data, $baseurl)) {
				$image = $this->request($imageUrl, $this->image, array(
					'Referer' => $baseurl
				));
			}
		} catch(Exception $e) {
			Intra_CMS()->dfb($e->getMessage());
			return false;
		}

		// Save image if we got one
		if($image) {
			$ext = $this->image[$image->headers['Content-Type']];
			$path = file_create_path($this->filepath.'/'.$file.'.'.$ext);
			if($path = file_save_data($image->data, $path, FILE_EXISTS_REPLACE)) {
				// Last validation, now as image is saved in file.
				try {
					if($this->validImage($path))
						return $path;
				} catch( Exception $e ) {
					unlink($path);
					Intra_CMS()->dfb($e->getMessage());
				}
			}
		}

		return false;
	}

	/**
	 * Generate filename from url
	 */
	public function file($url) {
		$url = str_replace('/', '', $url);

		if(module_exists('transliteration')) {
			require_once (drupal_get_path('module', 'transliteration') . '/transliteration.inc');
			$file = transliteration_clean_filename($url);
		} else {
			$file = preg_replace('/[^a-zA-Z0-9_]+/', '_', $url);
		}

		$file = file_munge_filename($file, implode(' ', $this->image), false);
		return $file;
	}

	/**
	 * Check for existing logo files
	 */
	public function checkFile($file) {
		$types = implode('|', $this->image);
		$path = file_create_path($this->filepath);
		file_check_directory($path, true);
		if($files = file_scan_directory($path, '^'.preg_quote($file).'\.('.$types.')$')) {
			return current($files)->filename;
		}
		return false;
	}

	/**
	 * Perform request to $url.
	 * @param $url String
	 *   Url to retrieve
	 * @param $mimes Array
	 *   Mime types to allow
	 * @param $headers Array
	 *   Additional headers for request
	 * @return Array
	 *   Drupal request resource
	 */
	protected function request($url, $mimes, $headers=array()) {
		// $i prevents too much recursion
		static $i = 0;
		if(!check_url($url)) {
			throw new UnexpectedValueException('Invalid url: '.$url);
		}
		
		$data = drupal_http_request($url, $headers);
		if($data && $data->error == 'missing schema' && $data->redirect_url && $i < 3) {
			$i++;
			$redirUrl = $this->generateUrl($data->redirect_url, $url);
			if($redirUrl != $url) 
				$data = $this->request($redirUrl, $mimes, $headers);
			$i--;
		}
		if($data && $this->requestCheck($data, $mimes)) {
			return $data;
		}
		return false;
	}

	protected function requestCheck($request, $mimes) {
		if(!$request) {
			throw new LogicException('Request data missing.');
			return false;
		} elseif($request->code != '200' && $request->redirect_code != '200') {
			throw new LogicException('Did not receive valid response code.');
		} elseif(!$this->validMime($request->headers, $mimes)) {
			throw new LogicException('Invalid mime type: '.$request->headers['Content-Type']);
		} elseif(isset($data->headers['Content-Length']) && $data->headers['Content-Length'] <= 0) {
			throw new LogicException('Content length is too short: '.$request->headers['Content-Length']);
		}
		return true;
	}

	protected function validMime($headers, $valid) {
		list($type) = explode(';', $headers['Content-Type']);
		$type = strtolower(trim($type));
		return (isset($valid[$type])) ? true : false;
	}

	/**
	 * Use drupal file validation to validate image
	 */
	protected function validImage($path) {
		// Create drupal compatible file class
		$file = new stdClass;
		$file->filepath = $path;

		if(function_exists('file_validate_is_image')) {
			if(list($err) = file_validate_is_image($file)) {
				throw new UnexpectedValueException($err);
				return false;
			}
		}
		if(function_exists('file_validate_image_resolution')) {
			if(list($err) = file_validate_image_resolution($file, $this->maxSize, $this->minSize)) {
				throw new OutOfRangeException($err);
				return false;
			}
		}
		return true;
	}

	protected function seekImageUrl($page, $baseurl) {
		$imgurl = false;

		// Remove all odd characters
		$nameRegexp = '/[^a-zA-Z0-9]+/';
		$cname = strtolower(preg_replace($nameRegexp, '', $this->getReference()->get('c_cname')));

		if(preg_match_all($this->logoRegex, $page, $match, PREG_SET_ORDER)) {

			foreach($match as $candidate) {
				$imgSrc = trim($candidate[1],'"\'');
				if(stristr($imgSrc,'logo')) {
					$imgurl = $imgSrc;
					break;
				}

				$candName = $imgSrc;
				if(($slashPos = strrpos($candName,'/')) !== false)
					$candName = substr($candName, $slashPos+1);

				if($extPos = strrpos($candName, '.'))
					$candName = substr($candName, 0, $extPos);

				$candName = strtolower(preg_replace($nameRegexp, '', $candName));
				similar_text( $cname, $candName, $similarity );

				Intra_CMS()->dfb("Similarity $cname to $candName is $similarity");
				if($similarity > 75 || stristr($candName, $cname)) {
					// Meh, good enought
					$imgurl = $imgSrc;
					break;
				}
			}

			if($imgurl) {
				$imgurl = $this->generateUrl($imgurl, $baseurl);
			}
		}

		return $imgurl;
	}

	/**
	 * Tries to absolute generate urls.
	 */
	protected function generateUrl($url, $baseurl) {
		$uri = parse_url($baseurl);

		// Not 100% sure way, but close enought for now
		if(substr($url,0,2) == '//') {
			// Xiit url
			$url = ltrim($url, '/');
			$url = substr($url, strpos($url, '/'));
			$url = sprintf('%s://%s/%s', $uri['scheme'], $uri['host'], $url); 
		} elseif($url[0] == '/') {
			// Absolute url
			$url = sprintf('%s://%s%s', $uri['scheme'], $uri['host'], $url);

		} elseif (!strpos($url, '://')) {
			// Relative path
			$path = $uri['path'];
			if($path[strlen($path)-1] != '/') {
				$path = dirname($path);
			}
			if($path[0] != '/') {
				$path = '/';
			}
			if($url[0] != '/' && $path[strlen($path)-1] != '/') {
				$path .= '/';
			}

			$url = sprintf('%s://%s%s%s', $uri['scheme'], $uri['host'], $path, $url);
		} else {
			Intra_CMS()->dfb('Unknown url syntaxt: '.$url);
		}
		return $url;
	}
}
