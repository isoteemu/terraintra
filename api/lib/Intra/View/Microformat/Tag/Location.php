<?php
/**
 * Convert Intra_Object_Gis_Point into html presentation.
 */
class Intra_View_Microformat_Tag_Location extends Intra_View_Microformat_Tag {

	public static function factory($val='', $tagName = null, $escape = true, $class=null) {

		if($val instanceOf Intra_Object_Gis_Point) {
			$str  = '';
			$str .= (string) self::getLatitude($val)->__toString();
			$str .= (string) self::getLongitude($val)->__toString();

			$tag = parent::factory($str, $tagName, false, __CLASS__);
			$tag->addClass('location');
		} else {
			$tag = parent::factory($val, $tagName, $escape, get_parent_class(__CLASS__));
		}

		return $tag;
	}

	public function getLongitude($geo) {
		$x = $geo->lon;
		$tag = self::_locationTag($x);
		$tag->addClass('longitude');

		$tag->addChild('span', ($x < 0) ? 'W' : 'E');
		return $tag;
	}

	public function getLatitude($geo) {
		$y = $geo->lat;
		$tag = self::_locationTag($y);
		$tag->addClass('latitude');

		$tag->addChild('span', ($y < 0) ? 'S' : 'N');

		return $tag;
	}

	protected function _locationTag($deg) {
		list($degrees, $minutes, $seconds) = self::degToDms($deg);
		$tag = self::factory('', 'abbr');
		$tag->addChild('span', $degrees.'°')->addAttribute('class', 'degrees');
		$tag->addChild('span', $minutes.'\'')->addAttribute('class', 'minutes');
		$tag->addChild('span', $seconds.'"')->addAttribute('class', 'second');

		$tag->addAttribute('title', $deg);

		return $tag;
	}

	/**
	 * Convert degrees into Degrees, Minutes, Seconds.
	 * @param $dec Int
	 *   Decrees to convert
	 * @return Array
	 *	 Decrees, Minutes, Seconds
	 */
	protected function degToDms($coord) {

		$coord = abs($coord);
		$degrees = floor($coord);
		$coord -= $degrees;
		$coord *= 60;
		$minutes = floor($coord);
		$coord -= $minutes;
		$coord *= 60;
		$seconds = round($coord);

		return array(
			$degrees,
			$minutes,
			$seconds
		);
	}
}

