<?php

/**
 * Description of Team
 *
 * @author user
 * @property Member $users Users that Team have
 */
class Team extends Model {
	/**
	 * @param class $model
	 * @return Content
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);
	}

	public function relations() {
		return array(
			'users' => array(self::HAS_MANY, 'Member', 'id'),
		);
	}
	
	public function tableName() {
		return 'test_team';
	}
}

?>
