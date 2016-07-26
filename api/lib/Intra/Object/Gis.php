<?php

// WKB types
if(!defined('GIS_TYPE_POINT'))
	define('GIS_TYPE_POINT', '1');
if(!defined('GIS_TYPE_LINESTRING'))
	define('GIS_TYPE_LINESTRING', '2');
if(!defined('GIS_TYPE_POLYGON'))
	define('GIS_TYPE_POLYGON', '3');
if(!defined('GIS_TYPE_MULTIPOINT'))
	define('GIS_TYPE_MULTIPOINT', '4');
if(!defined('GIS_TYPE_MULTILINESTRING'))
	define('GIS_TYPE_MULTILINESTRING', '5');
if(!defined('GIS_TYPE_MULTIPOLYGON'))
	define('GIS_TYPE_MULTIPOLYGON', '6');
if(!defined('GIS_TYPE_GEOMETRYCOLLECTION'))
	define('GIS_TYPE_GEOMETRYCOLLECTION', '7');

class Intra_Object_Gis {

	/**
	 * Type maps to objects.
	 */
	public static $classMap = array(
		GIS_TYPE_POINT => 'Intra_Object_Gis_Point'
	);

	public static function fromWKB($binary) {
		$shape = unpack('Corder/Ltype',$binary);

		switch($shape['type']) {
			case GIS_TYPE_POINT :
			case GIS_TYPE_LINESTRING :
			case GIS_TYPE_POLYGON :
			case GIS_TYPE_MULTIPOINT :
			case GIS_TYPE_MULTILINESTRING :
			case GIS_TYPE_MULTIPOLYGON :
			case GIS_TYPE_GEOMETRYCOLLECTION :

				$geo = call_user_func(array(self::$classMap[$shape['type']], 'fromWKB'), $binary);

				break;
			default :
				throw new UnexceptedValue('Unknown type '.$shape['type'].' for value '.$binary);
				break;
		}
		return $geo;
	}
}
