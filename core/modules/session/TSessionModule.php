<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 16.01.13
 * Time: 17:31
 * To change this template use File | Settings | File Templates.
 */
class TSessionModule extends TModule {

	const SKEY_REGEXP = '/[0-9a-f]{32}/';
	const U_ANONIM = -1;
	const U_BOT = -2;
	const DEFAULT_NAME = 'Guest';
	const SKEY_GET = 1;
	const SKEY_COOKIE = 2;
	const SKEY_OTHER = 3;

	protected $_id = self::U_ANONIM;
	protected $_name = self::DEFAULT_NAME;
	protected $_ip = '0.0.0.0';
	protected $_logged = false;
	protected $_admin = false;
	protected $_bot = false;
	protected $_blocked = false;
	protected $_data = array();
	protected $_skey = '';
	protected $_skeytype = self::SKEY_OTHER;
	protected $_abuse = '';
	protected $_time = 0;
	protected $_lastUpdateTime = 0;
	protected $_loginStartTime = 0;
	protected $_posts = 0;

	public function init() {
		$this->_user = new User();
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
