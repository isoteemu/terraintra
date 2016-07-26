<?php
/**
 * A time -tag
 */
class Intra_View_Microformat_Tag_Time extends Intra_View_Microformat_Tag {

	public static function &factory($val='', $tagName = 'time', $escape = true, $class=null) {
		if($val) {
			if(module_exists('date_api')) {
				$datedate = date_make_date($val);
				$val = date_format_date($datedate, 'small');
				$title = date_format_date($datedate);
				$datetime = date_format_date($datedate, 'custom', 'c');
			} else {
				$time = strtotime($val);
				$val = format_date($time, 'small');
				$title = format_date($time);
				$datetime = format_date($time, 'custom', 'c');
			}
		}

		$tag = parent::factory($val, $tagName, $escape, __CLASS__);
		if($datetime) {
			$tag->setAttributes(array(
				'title' => $title,
				'datetime' => $datetime
			));
		}
		return $tag;
	}

}
