<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 17.01.13
 * Time: 13:31
 * To change this template use File | Settings | File Templates.
 */


class User extends Model {

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
