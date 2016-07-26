<?php
/**
 * Webfrom event.
 */

class Intra_Activity_Event_Lataus20 implements Intra_Activity_Event, Intra_Activity_TypedEvent {

	protected $title;
	protected $body;
	protected $date;
	protected $contact;
	protected $direction = Intra_Activity_Event::EVENT_SYSTEM;

	protected $payload;

	protected $type = 'lataus20';

	public function __construct($row) {
		foreach($row as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function getTitle() {
		if(module_exists('intra_search_company')) {
			$title = l(
				$this->title,
				'search/intra_search_company/'.urlencode($this->title)
			);
		} else {
			$title = theme('placeholder', $this->title);
		}

		$t = t('[Download] for !title', array(
			'!title' => $title
		));
		return $t;
	}
	public function getBody() {
		return $this->body;
	}
	public function getDate() {
		return $this->date;
	}
	public function getActions() {
		$links = array();

		if(module_exists('intra_search_company')) {
			$links[] = l(
				t('Company'),
				'search/intra_search_company/'.urlencode($this->title)
			);
		}
		return $links;
	}
	public function getContact() {
		return null;
	}

	public function getDirection() {
		return $this->direction;
	}

	public function getType() {
		return $this->type;
	}
}
