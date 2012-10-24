<?php

/**
 * @property model() Content
 */
class Content extends Model {

	/**
	 * @param class $model
	 * @return Content
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);
	}

	public function relations() {
		return array(
			'comments' => array(self::HAS_MANY, 'Comments', 'content_id'),
			'autor' => array(self::BELONGS_TO, 'User', 'user_id'),
			'tags' => array(self::HAS_MANY, 'Tags',)
		);
	}

}
