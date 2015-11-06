<?php
/**
 * @property string username
 * @property bool   logged
 * @property string color
 * @property string avatar
 * @property string loginUrl
 * @property string registerUrl
 * @property string logoutUrl
 * @property string profileUrl
 */
class UserWidget extends Widget
{

    public function onCall()
    {
        $this->data();
        return false;
    }

    public function data()
    {
        $isLoggedIn = Core::app()->session->authenticated();
        $color = ($isLoggedIn) ? ' color: red;' : '';

        $this->username = ($isLoggedIn) ? Core::app()->session->user()->name : SessionModule::DEFAULT_NAME;
        $this->logged = $isLoggedIn;
        $this->color = $color;
        $this->avatar = '';
        $this->loginUrl = Html::url('//user/login');
        $this->registerUrl = Html::url('//user/register');
        $this->logoutUrl = Html::url('//user/logout');
        $this->profileUrl = Html::url('//user/profile');
    }
}
