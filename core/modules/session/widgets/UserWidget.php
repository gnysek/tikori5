<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 18.01.13
 * Time: 15:10
 * To change this template use File | Settings | File Templates.
 * @property string username
 * @property bool logged
 * @property string color
 * @property string avatar
 * @property string loginUrl
 * @property string registerUrl
 * @property string logoutUrl
 * @property string profileUrl
 */
class UserWidget extends Widget {

	public function run() {
		return false;
	}

	public function data() {
		return (object) array(
			'username'    => SessionModule::DEFAULT_NAME,
			'logged'      => false,
			'color'       => '',
			'avatar'      => '',
			'loginUrl'    => Html::url('//user/login'),
			'registerUrl' => Html::url('//user/register'),
			'logoutUrl'   => Html::url('//user/logout'),
			'profileUrl'  => Html::url('//user/profile'),
		);
	}
}
