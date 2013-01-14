<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ContentTranslation
 *
 * @author user
 * @property Content $content
 * @property int $id
 * @property int $page_id
 * @property int $language_id
 * @property string $name
 * @property string $short
 * @property string $long
 * @property string $img
 * @property string $url
 * @property int $comm
 */
class ContentTranslation extends Model {

	//put your code here

	protected $_table = 'content_translation';

	/**
	 * @param class $model
	 * @return ContentTranslation
	 */
	public static function model($model = __CLASS__) {
		return parent::model($model);
	}

	public function relations() {
		return array(
			'content' => array(self::BELONGS_TO, 'Content', 'page_id')
		);
	}

}

?>
