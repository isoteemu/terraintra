<?php
/**
 * vcard presentation for person
 */

class Intra_View_VCard_Person extends Intra_View_VCard {

	public function init() {
		parent::init();
		// Construct from reference
		$contact =& $this->getReference();

		$oldMode = $contact->getMode(Person::GETMODE_RECURSE);


		$this->vCard->setFormattedName($this->getFn());
		$this->vCard->setName(
			$contact->get('p_lname'),
			$contact->get('p_fname'),
			'', // Middle names
			'', // Prefix
			''  // Suffix
		);

		$this->vCard->addOrganization($contact->getCompany()->get('c_cname'));

		$this->vCard->addEmail($contact->get('p_email'));
		$this->vCard->addParam('TYPE', 'WORK');

		$this->vCard->addAddress(
			$contact->get('p_box'),
			'', // Extended
			$contact->get('p_street'),
			$contact->get('p_city'),
			'',
			$contact->get('p_zip'),
			$contact->get('p_country')
		);
		$this->vCard->addParam('TYPE', 'WORK');

		$this->vCard->addTelephone($contact->get('p_phone'));
		$this->vCard->addParam('TYPE', 'WORK');

		$this->vCard->addTelephone($contact->get('p_telefax'));
		$this->vCard->addParam('TYPE', 'FAX');

#		$this->vCard->setNote(base64_encode($contact->get('p_rem')));
#		$this->vCard->addParam('ENCODING', 'B');

		// Add photo
		try {
			if($photo = $this->photo()) {
				$img = $this->_image($photo);
				$this->vCard->setPhoto($img['data']);
				$this->vCard->addParam('TYPE', $img['type']);
				$this->vCard->addParam('ENCODING', $img['encoding']);
			}
		} catch(InvalidArgumentException $e) {
			dfb('Photo image is invalid: '.$e->getMessage());
		}

		// Add Company logo
		try {
			if($logo = $contact->getCompany()->get('c_logo')) {
				$img = $this->_image($logo);
				$this->setPhoto($img['data']);
				$this->addParam('TYPE', $img['type']);
				$this->addParam('ENCODING', $img['encoding']);
			}
		} catch(InvalidArgumentException $e) {
			dfb('Logo image is invalid: '.$e->getMessage());
		}

		/// TODO: Add revisions
		//$this->vCard->setRevision();

		$contact->getMode($oldMode);
	}

	public function getFn() {
		$fname = (string) $this->get('p_fname');
		$lname = (string) $this->get('p_lname');
		return t('!fname !lname', array('!fname' => $fname, '!lname' => $lname));
	}
}