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
class ContentTranslation extends TModel
{

    //put your code here

    protected $_table = 'content_translation';
    protected $_languages;
    protected $_language;

    /**
     * @param null|string $model
     *
     * @return ContentTranslation|TModel
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

    public function rules()
    {
        // array( field[s], ruleName, ruleOptions)
        return array(
            array(array('page_id', 'language_id', 'name', 'short'), 'required'),
            array(array('id', 'page_id', 'language_id', 'comm'), 'int'),
//            array(array('name', 'short', 'long', 'img', 'url'), 'text'),
            array(array('name', 'img', 'url'), 'len', 'maxlen' => 255),
            array(array('long', 'img'), 'null'),
        );

        //TODO: scenarios - can add ON parameter, and when creating model scenario can be attached as first arg
    }

    //TODO: should be a relation!
    public function getLanguage()
    {
        if (empty($this->_language)) {
            $lang = Language::model()->load($this->language_id);
            $this->_language = $lang->language_code;
        }

        return $this->_language;
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
