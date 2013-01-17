<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 16.01.13
 * Time: 17:31
 * To change this template use File | Settings | File Templates.
 */
class TSessionModule {

	const SKEY_REGEXP = '/[0-9a-f]{32}/';

	private $_skey = '';
	private $_time = 0;
	private $_lastUpdateTime = 0;
	private $_user = null;
	private $_bot = false;

	public function init() {
		$this->_user = new UserModule();
		Core::register('user', $this->_user);
		Core::register('session', $this);
		$this->startSession();
	}

	public function startSession() {
		$this->_time = $this->_lastUpdateTime = time();

		$cookie = new Cookie();

		if ($key = $cookie->get('tk5_sid', null)) {
			if (preg_match(self::SKEY_REGEXP, $key)) {
				$this->_skey = $key;
			}
		} else if (!empty($_GET['sid']) && preg_match(self::SKEY_REGEXP, $_GET['sid'])) {
			$this->_skey = $_GET['sid'];
		}

		if (empty($this->_skey)) {
			$this->_newSession();
		} else {
			$this->_continueSession();
		}
	}

	protected function _newSession() {

	}

	protected function _continueSession() {

	}

	public function isBot() {
		return $this->_bot;
	}


}
