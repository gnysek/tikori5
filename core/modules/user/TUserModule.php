<?php

/**
 * @author Piotr Gnys <gnysek@gnysek.pl>
 * @package user
 */
class TUserModule {

	const U_ANONIM = -1;
	const U_BOT = -2;
	const DEFAULT_NAME = 'Guest';
	const SKEY_GET = 1;
	const SKEY_COOKIE = 2;
	const SKEY_OTHER = 3;

	private $_id = self::U_ANONIM;
	private $_name = self::DEFAULT_NAME;
	private $_ip = '0.0.0.0';
	private $_logged = false;
	private $_admin = false;
	private $_bot = false;
	private $_blocked = false;
	private $_data = array();
	private $_skey = '';
	private $_skeytype = self::SKEY_OTHER;
	private $_abuse = '';
	private $_time = 0;
	private $_loginStartTime = 0;
	private $_posts = 0;

	public function __construct() {
		$this->_ip = Core::app()->cfg('request/ip');
	}

	public function isLogged() {
		return $this->_logged;
	}

	public function isAdmin() {
		return $this->_admin;
	}

}
