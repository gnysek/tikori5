<?php

/**
 * Description of Content
 *
 * @author user
 * @property int $created
 * @property int $updated
 */
class Content extends Model {

//	protected $_pkId = 'id';

	/**
	 * @param class $model
	 *
	 * @return Content
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);
	}

	public function getTable() {
		return 'content';
	}

	public function relations() {
		return array(
			'content_translations' => array(self::HAS_MANY, 'ContentTranslations', 'content_id'),
		);
	}

	public function getFields() {
		return array(
			'id',
			'name',
			'enabled',
			'path',
			'parent',
			'type',
			'created',
			'updated',
			'comments',
			'author',
		);
	}

	public function rules() {
		return array(
			array('name, enabled, path, type, created, updated, comments, author', 'required'),
			array('id, enabled, parent, type, created, updated, comments, author', 'int'),
			array('name', 'maxlen', 255),
		);
	}

	protected function _beforeSave() {
		$time = time();
		if ($this->_isNewRecord) {
			$this->created = $time;
		}
		$this->updated = $time;
	}

}

?>
