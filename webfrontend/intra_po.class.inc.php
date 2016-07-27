<?php

// Fuck me in the ass
function getItemKey($iter, $haystack) {
	$k = array_keys($haystack);
	return $haystack[$k[$iter]];
}
function nextItem($needle, $haystack) {
	$key = preg_quote($needle);
	return (preg_match('/(^|\$)'.$needle.'\$([^\$]+)/', implode('$', array_keys($haystack)), $match)) ? $match[2] : false;
}
function itemIter($needle, $haystack) {
	return array_search($needle, array_keys($haystack));
}

class poLine {
	public $x;
	public $y;
	public $text;

	const topLeft = 1, center = 2;

	function distanceFrom($to, $by = poLine::center) {

		switch($by) {
			case poLine::center :
				$x1 = ($this->x + $this->width) / 2;
				$y1 = ($this->y + $this->height) / 2;

				$x2 = ($to->x + $to->width) / 2;
				$y2 = ($to->y + $to->height) / 2;
				break;
			case poLine::topLeft :
				$x1 = $this->x;
				$y1 = $this->y;

				$x2 = $to->x;
				$y2 = $to->y;
				break;
		}
		if($x1 == $x2 && $y1 == $y2) return 0;

		$theta = $x1 - $x2; 
		$dist = sin(deg2rad($y1)) * sin(deg2rad($y2)) +  cos(deg2rad($y1)) * cos(deg2rad($y2)) * cos(deg2rad($theta)); 
		$dist = acos($dist);
		$dist = rad2deg($dist);
		return $dist;
	}
}

/**
 * This fucking sucks.
 */

// LOT on O(n^2)
class po {
	public $products = array(
		'TerraPhoto Viewer',
		'TerraScan Viewer',
		'TerraScan',
		'TerraModeler',
		'TerraPhoto',
		'TerraMatch'
	);

	public $entrys = array();

	public $lines = array();
	public $cols  = array();

	public $height = 0;
	public $width  = 0;

	public $lineHeight = 3;
	public $colWidth  = 20;

	public function __construct($file=null) {
		if($file !== null) {
			putenv('DISPLAY');
			$cmd = sprintf('/usr/bin/pdftohtml -xml -stdout %s', escapeshellarg($file));
			exec($cmd, $output, $status);
			if($status) {
				throw new Exception('PDF conversion to XML failed: '.implode("\n", $output));
				return false;
			}
			return $this->parseXml(implode("\n", $output));
		}
	}

	public function parseXml($xml) {
		preg_match_all('/<text[^>]*>(.*)<\/text>/miS', $xml, $match);

		$this->height = (preg_match('/<page [^>]*height="([^"]+)"/', $xml, $h)) ? $h[1] : 792;
		$this->width = (preg_match('/<page [^>]*width="([^"]+)"/', $xml, $w)) ? $w[1] : 612;

		$i = 0;

		foreach(array_keys($match[0]) as $key) {
			$style = $match[0][$key];
			$text  = $match[1][$key];

			$point = new poLine;
			if(preg_match('/ top="([^"]+)"/', $style, $top) &&
				preg_match('/ left="([^"]+)/', $style, $left) &&
				preg_match('/ width="([^"]+)/', $style, $width) &&
				preg_match('/ height="([^"]+)/', $style, $height)) {
				$point->x     = $left[1];
				$point->y     = $top[1];
				$point->width = $width[1];
				$point->height = $height[1];
				$point->text  = strip_tags($text);

				$i++;
				$this->entrys[$i] = $point;

				// Seek indexes
				$point->line = round($point->y/$this->lineHeight);
				$point->col = round($point->x/$this->colWidth);


				$this->lines[$point->line][$point->col] = $i;
				$this->cols[$point->col][$point->line] = $i;

			}
		}

		ksort($this->lines);
		foreach(array_keys($this->lines) as $line) {
			ksort($this->lines[$line]);
		}

		ksort($this->cols);
		foreach(array_keys($this->cols) as $col) {
			ksort($this->cols[$col]);
		}
	}

	public function getItems() {
		$products = array();
		// Find description column
		$desc = false;
		foreach($this->entrys as $line) {
			if(!$desc && preg_match('/Description/i', $line->text)) {
				$desc = $line;
			} elseif(!isset($qty) && preg_match('/(Qty|Quantity)/i', $line->text)) {
				$qty = itemIter($line->col, $this->lines[$line->line]);
			}
		}

		// Loop thru lines
		foreach($this->lines as $line) {
			if($this->entrys[current($line)]->y <= $desc->y) continue; // Skip above items

			$dist = 9999;
			$nearest = false;
			foreach($line as $c => $e) {
				$cdist = $desc->distanceFrom($this->entrys[$e]);
				if($cdist > 40) continue;
				if($dist > $cdist) {
					$dist = $cdist;
					$nearest = $this->entrys[$e];
				}
			}
			if($nearest) {
				$desc = $nearest;
				$q = (isset($qty)) ? $this->entrys[getItemKey($qty, $this->lines[$desc->line])]->text : 1;
				settype($q, 'int');
				if(preg_match_all('/('.implode('|', $this->products).')/iU', $desc->text, $m)) {
					foreach($m[1] as $pr) {
						$products[$pr] = (!isset($products[$pr])) ? $q : $products[$pr] + $q;
					}
				}
			} else {
				break;
			}
		}
		return $products;
	}

	public function poInfo($key) {
		switch($key) {
			case 'date' :
				$reg = '(Date)';
				break;
			case 'po' :
				$reg = '(P\.O\.|Number)';
				break;
			default :
				$reg = preg_quote($key);
				break;
		}

		$poHdr = false;

		foreach($this->entrys as $line) {
			if(preg_match('/(Purchase Order|Purchasing Order)/i', $line->text)) {
				$poHdr = $line;
				continue;
			} elseif($poHdr) {
				if(preg_match('/'.$reg.'/i', $line->text)) {
					$nextLine = nextItem($line->line, $this->lines);
					$nextCol  = nextItem($line->col, $this->cols);
					if(preg_match('/:$/', $line->text) ) {
						// Prefer next column - easy
						$nextCol = nextItem($line->col, $this->lines[$line->line]);
						$e = $this->lines[$line->line][$nextCol];
						return $this->entrys[$e]->text;
					} else {
						// Get nearest
						$dist = 9999;
						$near = false;
						foreach($this->lines[$nextLine] as $e) {
							$cdist = $line->distanceFrom($this->entrys[$e]);
							if($dist > $cdist) {
								$dist = $cdist;
								$near = $this->entrys[$e];
							}
						}
						return $near->text;
					}
				}
			}
		}
	}

	public function address() {
		$address = array();
		$hdr = false;
		foreach($this->lines as $line) {
			$dist = 9999;
			$near = false;
			foreach($line as $e) {
				if($hdr) {
					$cdist = $hdr->distanceFrom($this->entrys[$e]);
					if($dist > $cdist) {
						$dist = $cdist;
						$near = $this->entrys[$e];
					}
				} elseif(preg_match('/(Ship To|End User)/i', $this->entrys[$e]->text)) {
					$hdr = $this->entrys[$e];
					continue;
				}
			}

			if($hdr && $near) {
				if($hdr->y + 30 > $near->y && $hdr->distanceFrom($near, poLine::topLeft) < 30) {
					$hdr = $near;
					$address[] = $near->text;
				} else {
					break;
				}
			}
		}
		return $address;
	}

	/**
	 * Parse Address. Taken from Horde Form -api.
	 * @param $address Optional string address.
	 */
	public function parseAddress(string $address=null) {
		if($address === null) $address = $this->address();

		$info = array();
		$aus_state_regex = '(?:ACT|NSW|NT|QLD|SA|TAS|VIC|WA)';

		if (preg_match('/(?s)(.*?)(?-s)\r?\n(?:(.*?)\s+)?((?:A[BL]|B[ABDHLNRST]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[CHNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTWY]?|T[ADFNQRSW]|UB|W[ACDFNRSV]?|YO|ZE)\d(?:\d|[A-Z])? \d[A-Z]{2})/', $address, $addressParts)) {
			/* UK postcode detected. */
			$info = array('country' => 'uk', 'zip' => $addressParts[3]);
			if (!empty($addressParts[1])) {
				$info['street'] = $addressParts[1];
			}
			if (!empty($addressParts[2])) {
				$info['city'] = $addressParts[2];
			}
		} elseif (preg_match('/\b' . $aus_state_regex . '\b/', $address)) {
			/* Australian state detected. */
			/* Split out the address, line-by-line. */
			$addressLines = preg_split('/\r?\n/', $address);
			$info = array('country' => 'au');
			for ($i = 0; $i < count($addressLines); $i++) {
				/* See if it's the street number & name. */
				if (preg_match('/(\d+\s*\/\s*)?(\d+|\d+[a-zA-Z])\s+([a-zA-Z ]*)/', $addressLines[$i], $lineParts)) {
					$info['street'] = $addressLines[$i];
					$info['streetNumber'] = $lineParts[2];
					$info['streetName'] = $lineParts[3];
				}
				/* Look for "Suburb, State". */
				if (preg_match('/([a-zA-Z ]*),?\s+(' . $aus_state_regex . ')/', $addressLines[$i], $lineParts)) {
					$info['city'] = $lineParts[1];
					$info['state'] = $lineParts[2];
				}
				/* Look for "State <4 digit postcode>". */
				if (preg_match('/(' . $aus_state_regex . ')\s+(\d{4})/', $addressLines[$i], $lineParts)) {
					$info['state'] = $lineParts[1];
					$info['zip'] = $lineParts[2];
				}
			}
		} elseif (preg_match('/(?s)(.*?)(?-s)\r?\n(.*)\s*,\s*(\w+)\.?\s+(\d+|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d)/', $address, $addressParts)) {
			/* American/Canadian address style. */
			$info = array('country' => 'us');
			if (!empty($addressParts[4]) &&
				preg_match('|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d|', $addressParts[4])) {
				$info['country'] = 'ca';
			}
			if (!empty($addressParts[1])) {
				$info['street'] = $addressParts[1];
			}
			if (!empty($addressParts[2])) {
				$info['city'] = $addressParts[2];
			}
			if (!empty($addressParts[3])) {
				$info['state'] = $addressParts[3];
			}
			if (!empty($addressParts[4])) {
				$info['zip'] = $addressParts[4];
			}
		} elseif (preg_match('/(?:(?s)(.*?)(?-s)(?:\r?\n|,\s*))?(?:([A-Z]{1,3})-)?(\d{4,5})\s+(.*)(?:\r?\n(.*))?/i', $address, $addressParts)) {
			/* European address style. */
			$info = array();
			if (!empty($addressParts[1])) {
				$info['street'] = $addressParts[1];
			}
			if (!empty($addressParts[2])) {
				if(@include('Horde/NLS/carsigns.php')) {
					$country = array_search(strtoupper($addressParts[2]), $carsigns);
					if ($country) {
						$info['country'] = $country;
					}
				}
			}
			if (!empty($addressParts[5])) {
				if(@include('Horde/NLS/countries.php')) {
					$country = array_search($addressParts[5], $countries);
				} else {
					$country = false;
				}
				if ($country) {
					$info['country'] = String::lower($country);
				} elseif (!isset($info['street'])) {
					$info['street'] = trim($addressParts[5]);
				} else {
					$info['street'] .= "\n" . $addressParts[5];
				}
			}
			if (!empty($addressParts[3])) {
				$info['zip'] = $addressParts[3];
			}
			if (!empty($addressParts[4])) {
				$info['city'] = trim($addressParts[4]);
			}
		}

		return $info;
	}
}
