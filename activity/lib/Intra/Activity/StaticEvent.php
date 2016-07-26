<?php

/**
 * TODO
 */
abstract class Intra_Activity_StaticEvent extends Intra_Object implements Intra_Activity_Event {

	public $id;

	public $date	= '';
	public $title	= '';
	public $body	= '';

	public $payload	= array();

	public function load($id) {
		parent::load($id, 'Intra_Event_StaticEvent' );
		$this->payload = @unserialize($this->payload);
	}

	public function save() {
		$this->payload = @serialize($this->payload);
		$r = parent::save();
		$this->payload = @unserialize($this->payload);
	}

	public function date() {
		return $this->get('date');
	}

	public function title() {
		return $this->get('subject');
	}

	public function body() {
		return $this->get('body');
	}

	public function actions() {
		return array();
	}

	public function contact() {
		return Person::load($this->get('uid'));
	}

}
