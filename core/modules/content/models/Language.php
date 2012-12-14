<?php

class Language extends Model {

	protected $_pkId = 'language_id';

	/**
	 * @param class $model
	 * @return Content
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);
	}

	public function getTable() {
		return 'language';
	}

	public function relations() {
		return array(
			'content_translations' => array(self::HAS_MANY, 'ContentTranslations', 'language_id'),
		);
	}

	public function getFields() {
		return array(
			'language_id',
			'language_code'
		);
	}

	public function rules() {
		return array(
			array('language_code', 'required'),
			array('language_id', 'int'),
			array('language_code', 'maxlen', 255),
		);
	}

}
