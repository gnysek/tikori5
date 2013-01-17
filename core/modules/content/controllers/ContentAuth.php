<?php

class ContentAuth extends Controller {


	protected function _beforeRun() {
		if ($this->area == 'admin') {
			// set auth
			$this->checkPermissions = true;
		}

		return true;
	}

	public function checkPermissions() {
		$this->_beforeRun();
		parent::checkPermissions();
	}

	public function getPermissions() {
		return array(
			'?' => 'deny',
			'*' => 'deny',
			'@' => 'allow',
		);
	}
}
