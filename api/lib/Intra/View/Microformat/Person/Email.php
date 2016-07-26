<?php

class Intra_View_Microformat_Person_Email extends Intra_View_Microformat {
	protected $_accessors = array(
		'pe_email' => 'getEmail',
	);

	public function getEmail() {
		$mail = $this->getReference()->get('pe_email');

		$tag = 'a';
		if($this->getReference()->get('pe_optout') || $this->getReference()->get('pe_dead'))
			$tag = 'del';

		$tag = Intra_View_Microformat_Tag::factory($mail, $tag);
		$tag->addClass('email');

		if($this->getReference()->get('pe_primary'))
			$tag->addClass('pref');

		// $mail = sprintf('"%s" <%s>', (string) $this->getFn(), $mail);
		$tag->addAttribute('href', sprintf('mailto:%s', $mail));
		return $tag;
	}

	public function __toString() {
		try {
			return (string) $this->getEmail()->__toString();
		} catch(Exception $e) {
			dfb($e);
		}
		return $this->getReference()->get('pe_email');
	}

}