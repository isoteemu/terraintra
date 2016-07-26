<?php

interface Intra_Activity_Event {

	const EVENT_SYSTEM	= 0;
	const EVENT_TO		= 1;
	const EVENT_FROM	= 2;

	public function getTitle();
	public function getBody();
	public function getDate();
	public function getActions();
	public function getContact();

	public function getDirection();
}
