<?php

class Intra_Activity_Event_ActionTable implements Intra_Activity_Event {

	protected $title;
	protected $body;
	protected $date;
	protected $contact;
	protected $direction = Intra_Activity_Event::EVENT_TO;

	protected $payload;


	public function __construct($row) {
		foreach($row as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function getTitle() {
		return $this->title;
	}
	public function getBody() {
		$str = Intra_CMS()->filter($this->body);
		return $str;
	}

	public function getDate() {
		return $this->date;
	}

	public function getActions() {
		$links = array();
		if($this->payload['x_file']) {
			$links[] = l(
				t('File'),
				'https://www.example.com/terraintra/files/'.urlencode($this->payload['x_file']),
				array('attributes' => array(
					'class' => 'file-attachment',
				))
			);
		}
		if($this->payload['x_var'] && $this->payload['x_id']) {
			switch($this->payload['x_var']) {

			}
		}
		return $links;
	}
	public function getContact() {
		return $this->contact;
	}

	public function getDirection() {
		return $this->direction;
	}

	public function getType() {
		return $this->type;
	}
}
