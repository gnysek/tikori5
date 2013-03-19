<?php
/**
 * @property int    id
 * @property string name
 * @property string password
 * @property string loginkey
 * @property string email
 * @property int    sex
 * @property int    regdate
 * @property int    birthday
 * @property string from
 * @property string www
 * @property string messenger
 * @property string signature
 * @property string avatar
 * @property string settings
 * @property int    points
 * @property int    notifications_new
 * @property int    notifications_count
 * @property int    pm_count
 * @property int    post_count
 * @property int    last_visit_time
 * @property int    last_update_time
 * @property int    warns
 * @property int    ban
 * @property int    bantime
 * @property int    admin
 *
 * @method int getBanType
 * @method int getBanTime
 * @method bool setBan
 * @method bool unsetBan
 * @method bool isBot
 * @method bool isAdmin
 * @method bool isLocked
 * @method bool getLastVisit
 */


class User extends Model
{

    const USER_TYPE_ANONYMOUS = 1;
    const USER_TYPE_USER = 2;
    const USER_TYPE_ADMIN = 3;
    const USER_TYPE_BOT = 4;

    const BAN_TYPE_TIME = 1;
    const BAN_TYPE_PERMAMENT = 2;

    protected $_table = 'users';

    /**
     * @param null|string $model
     *
     * @return User|Model
     */
    public static function model($model = __CLASS__)
    {
        return parent::model($model);
    }

    public function getFields()
    {
        return array(
            'id',
            'name',
            'password',
            'loginkey',
            'email',
            'sex',
            'regdate',
            'birthday',
            'from',
            'www',
            'messenger',
            'signature',
            'avatar',
            'settings',
            'points',
            'notifications_new',
            'notifications_count',
            'pm_count',
            'post_count',
            'last_visit_time',
            'last_update_time',
            'warns',
            'ban',
            'bantime',
            'admin',
        );
    }

    public function beforeSave()
    {
        $this->last_update_time = time();

        return true;
    }

    public function login($data)
    {
        $result = $this->findBy('name', $data['login'], true);

        if (!empty($result) and $result->password == md5($data['pass'])) {
            $this->last_visit_time = $this->last_update_time + 1;
            $this->last_update_time = time();
            return $result;
        }

        return false;
    }
}
