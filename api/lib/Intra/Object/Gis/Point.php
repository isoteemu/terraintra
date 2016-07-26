<?php

/**
 * Point geometry type.
 */
class Intra_Object_Gis_Point implements ArrayAccess, IteratorAggregate {

	public $lon = 0.0;		///< Location longitude (x)
	public $lat = 0.0;		///< Location latitude (y)

	public function __construct($lon=null, $lat=null) {

		if((is_array($lon) || $lon instanceOf ArrayAccess)) {
			$this->lon = $lon['lon'];
			$this->lat = $lon['lat'];
		} else {
			if($lon) $this->lon = $lon;
			if($lat) $this->lat = $lat;
		}
	}

	/**
	 * Convert to "Well Known Text".
	 */
	public function toWKT() {
		return "POINT({$this->lon} {$this->lat})";
	}

	/**
	 * Generate SQL clause
	 */
	public function __toString() {
		return "GeomFromText('".$this->toWKT()."')";
	}

	/**
	 * Convert to "Well Known Binary"
	 */
	public function toWKB() {
		return '0101'.pack('dd', $this->lon, $this->lat);
	}

	/**
	 * Create new object from binary representation.
	 * @param $binary
	 *   Binary presentation of Point.
	 * @return Intra_Object_Gis_Point
	 */
	public static function fromWKB($binary) {

		$geo = unpack('Corder/Ltype/dlon/dlat', $binary);

		if(isset($this)) {
			$this->lon = $geo['lon'];
			$this->lat = $geo['lat'];

			return $this;
		} else {
			$c = get_class();
			return new $c($geo['lon'], $geo['lat']);
		}
	}

	/**
	 * @name IteratorAggregate interface functions
	 * @see http://www.php.net/manual/en/class.iteratoraggregate.php
	 * @{
	 */
    public function getIterator() {
        return new ArrayIterator($this);
    }
	/**
	 * @}
	 */

	/**
	 * @name ArrayAcces interface functions
	 * @see http://www.php.net/manual/en/class.arrayaccess.php
	 * @{
	 */
    public function offsetSet($offset, $value) {
        $this->{$offset} = $value;
    }
    public function offsetExists($offset) {
        return isset($this->{$offset});
    }
    public function offsetUnset($offset) {
        unset($this->{$offset});
    }
    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
	/**
	 * @}
	 */
}