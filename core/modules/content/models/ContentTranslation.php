<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ContentTranslation
 *
 * @author user
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
