<?php
/**
 * Webfrom event.
 */

class Intra_Activity_Event_Webform implements Intra_Activity_Event, Intra_Activity_TypedEvent {

	protected $title;
	protected $body;
	protected $date;
	protected $contact;
	protected $direction = Intra_Activity_Event::EVENT_FROM;

	protected $payload;

	protected $type = 'webform';

	public function __construct($row) {
		foreach($row as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function getTitle() {
		$t = t('[<a href="@url">Form submission</a>]: %title', array(
			'%title' => $this->title,
			'@url' => $this->getTarget()
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
		$links[] = l(
			t('View'),
			$this->getTarget()
		);
		return $links;
	}
	public function getContact() {
		return $this->contact;
	}

	public function getDirection() {
		return $this->direction;
	}

	public function getTarget() {
		return 'https://www.example.com/node/'.urlencode($this->payload['nid']).'?sid='.urlencode($this->payload['sid']);
	}

	public function getType() {
		return $this->type;
	}
}
