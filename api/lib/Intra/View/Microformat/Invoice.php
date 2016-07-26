<?php

/**
 * Stub view for maintenance agreements.
 * This is replaced by more complex one, if intra_invoice is loaded.
 */
class Intra_View_Microformat_Invoice extends Intra_View_Microformat {

	public function __toString() {
		$nr = check_plain($this->getReference()->get('in_nr'));
		return '<a href="'
			.url(intra_api_url($this->getReference()))
			.'" class="invoice" data-uid="'.$this->getUid().'">'.$nr.'</a>';
	}
}
