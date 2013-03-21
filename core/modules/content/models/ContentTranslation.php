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
 * @property int     $id
 * @property int     $page_id
 * @property int     $language_id
 * @property string  $name
 * @property string  $short
 * @property string  $long
 * @property string  $img
 * @property string  $url
 * @property int     $comm
 */
class ContentTranslation extends Model
{

    //put your code here

    protected $_table = 'content_translation';
    protected $_languages;

    /**
     * @param null|string $model
     *
     * @return ContentTranslation|Model
     */
    public static function model($model = __CLASS__)
    {
        return parent::model($model);
    }

    public function relations()
    {
        return array(
            'content' => array(self::BELONGS_TO, 'Content', 'page_id')
        );
    }

    public function getFields()
    {
        return array(
            'content',
            'id',
            'page_id',
            'language_id',
            'name',
            'short',
            'long',
            'img',
            'url',
            'comm',
        );
    }

    public function getLanguages()
    {
        if (empty($this->languages)) {
            $this->_languages = array();
            $languages = Language::model()->findAll();
            foreach ($languages as $language) {
                /* @var $langauge Language */
                $this->_languages[$language->language_id] = $language->language_code;
            }
        }

        return $this->_languages;
    }

}

?>
