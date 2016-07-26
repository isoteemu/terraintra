<?php
// No way of detecting current user, so return empty
class Intra_CMS_CallManager_Currentuser extends Intra_CMS_CallManager_Instance {
	protected $user;
	public function init() {
		$this->user = Person::factory('Person', array(
			'p_lname' => 'Anonymous',
			'c_id' => 0
		));
	}

	public function call() {
		return $this->user;
	}

}
