<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 16.01.13
 * Time: 17:31
 * To change this template use File | Settings | File Templates.
 */
class TSession {
	public function init() {
		$this->user = new User();
		Core::register('user', $this->user);
		Core::register('session', $this);
	}
}
