<?php

abstract class Intra_CMS_CallManager_Instance implements Intra_CMS_CallManager_Interface {

	public function init() {}

	public function factory() {
		return $this;
	}

}
