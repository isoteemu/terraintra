<?php
/**
 * Recordlist search filter.
 * Handles Intra_Object_Recordlist datatypes as strings, and 
 * searches entry from Intra_Object_Recordlist::list_separator
 * separated list.
 * @todo Can't use currently any other separator types than
 *   what is defined in Intra_Object_Recordlist::list_separator.
 *   So classes that extends that are screwed.
 * @see Intra_Object_Recordlist
 */

class Intra_Filter_Rule_Recordlist extends Intra_Filter_Rule_Regexp {

	public function init() {
		// Generate regex rule

		$separator = preg_quote(Intra_Object_Recordlist::list_separator);

		if(is_array($this->rule['value'])) {
			$vals =  array_map('preg_quote', $this->rule['value']);
			$regexp = implode("(\$|{$separator})|(^|{$separator})", $vals);
			$regexp = "(^|{$separator})({$regexp})(\$|{$separator})";
		} else {
			$regexp = preg_quote($this->rule['value']);
			$regexp = "(^|{$separator}){$regexp}(\$|{$separator})";
		}

		$this->rule['value'] = $regexp;
	}
}