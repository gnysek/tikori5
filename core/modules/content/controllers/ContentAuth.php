<?php

class ContentAuth extends Controller {


	public function afterConstruct() {
		if ($this->area == 'admin') {
			// set auth
			$this->checkPermissions = true;
		}
	}

	public function getPermissions() {
		return array(
			'?' => 'deny',
			'*' => 'deny',
			'@' => 'allow',
		);
	}
}
