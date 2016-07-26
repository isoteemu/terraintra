<?php

/**
 * Stub view for maintenance agreements.
 * This is replaced by more complex one, if intra_agreement is loaded.
 */
class Intra_View_Microformat_Agreement extends Intra_View_Microformat {

	public function __toString() {
		$ag = check_plain($this->getReference()->get('ag_nr'));
		return '<span class="agreement" data-uid="'.$this->getUid().'">'.$ag.'</span>';
	}
}

