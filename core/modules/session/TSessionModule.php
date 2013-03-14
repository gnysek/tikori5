<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 16.01.13
 * Time: 17:31
 * To change this template use File | Settings | File Templates.
 *
 * @property User    $_user
 * @property Session $_session
 */
class TSessionModule extends TModule
{

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
    /**
     * @var Session
     */
    protected $_session = null;
    /**
     * @var User
     */
    protected $_user = null;

    public function init()
    {
        $this->_user = User::model();
        $this->addObserver(Tikori::EVENT_BEFORE_DISPATCH);
        //$this->startSession();
    }

    public function beforeDispatchEvent()
    {
        $this->startSession();
    }

    public function startSession()
    {
        $this->_time = $this->_lastUpdateTime = time();

        $cookie = new Cookie();

        if ($key = $cookie->get('tk5_sid', null)) {
            if (preg_match(self::SKEY_REGEXP, $key)) {
                $this->_skey = $key;
                $this->_skeytype = self::SKEY_COOKIE;
            }
        } else {
            if (!empty($_GET['sid']) && preg_match(self::SKEY_REGEXP, $_GET['sid'])) {
                $this->_skey = $_GET['sid'];
                $this->_skeytype = self::SKEY_GET;
            }
        }

        if (empty($this->_skey)) {
            Profiler::addLog('New session');
            $this->_newSession();
        } else {
            Profiler::addLog('Old session');
            $this->_continueSession();
        }
    }

    protected function _newSession()
    {
        $this->_skeytype = self::SKEY_OTHER;
        Html::$sidAddon = $this->_skey;

        //		do
        //		{
        $this->_skey = md5(uniqid(mt_rand(), true));

        $cookie = new Cookie();
        $cookie->set('tk5_sid', $this->_skey);
        //		}

        //		while ( array_key_exists( $this->userSid, $this->sidKeys) );
        $this->_session = Session::model();
        $this->_session->sid = $this->_skey;
        $this->_session->save();
    }

    protected function _continueSession()
    {
        $this->_session = Session::model()->find($this->_skey);

        if ($this->_session === null) {
            return $this->_newSession();
        }

        if ($this->_skeytype == self::SKEY_OTHER) {
            Html::$sidAddon = $this->_skey;
        }

        if ($this->_session->user_id > 0) {
            $this->_user = User::model()->load($this->_session->user_id);
        }

        //		$this->_session->time = time();
        //        if ($this->_session->tim)
        //		$this->_session->save();
    }

    public function __destruct()
    {
        if ($this->_session->current_time > time() - 60) {
            $this->_session->save();
        }
    }

    public function isBot()
    {
        return $this->_session->userId == -2;
    }

    public function __get($name)
    {
        if (!empty($this->_session)) {
            return $this->_session->$name;
        }
        if (!empty($this->_user)) {
            return $this->_user->$name;
        }
    }


}
