<?php
/**
 * @file
 *   TerraIntra Codes -helper class.
 */

/**
 * Internal codes helper class.
 * Actual ORM representation is in (badly named) Codes_Helper
 * @todo Thease changes relatively rarely, so would be smart to cache them.
 * @see Codes_Helper
 */
class Codes extends Intra_Helper {
	public static $codes = array();

	public static function getCode($code, $value) {
		return self::map($code)->each()->filter(array('cd_value' => $value))->current();
	}

	/**
	 * Map code type (CD_CODE) to code table variable
	 */
	public static function &map($type) {
		if(!array_key_exists($type, self::$codes)) {
			self::$codes[$type] = Codes_Helper::load(array('cd_code' => $type));
		}
		return self::$codes[$type];
	}

	/**
	 * Return codes as array, where cd_value is key and cd_name value
	 * @return Array
	 */
	public static function arrayMap($type) {
		$codes = self::map($type);
		$r = array();
		foreach($codes as $cd)
			$r[$cd->get('cd_value')] = $cd->get('cd_name');
		return $r;
	}

}
