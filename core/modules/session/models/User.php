<?php
/**
 * @property int $id
 * @property unt $name
 * @property int $type
 * @property int $warns
 * @property int $posts
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


class User extends Model {

    const USER_TYPE_ANONYMOUS = 1;
    const USER_TYPE_USER = 2;
    const USER_TYPE_ADMIN = 3;
    const USER_TYPE_BOT = 4;

    const BAN_TYPE_TIME = 1;
    const BAN_TYPE_PERMAMENT = 2;

	protected $_table = 'users';

	/**
	 * @param class $model
	 *
	 * @return ContentTranslation
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);

	}
}
