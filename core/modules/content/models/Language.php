<?php

class Language extends Model
{

    protected $_primaryKey = 'language_id';

    /**
     * @param string $model
     *
     * @return Language|Model
     */
    public static function model($model = __CLASS__)
    {
        return parent::model($model);
    }

    public function getTable()
    {
        return 'language';
    }

    public function relations()
    {
        return array(
            'content_translations' => array(self::HAS_MANY, 'ContentTranslations', 'language_id'),
        );
    }

    public function rules()
    {
        return array(
            array('language_code', 'required'),
            array('language_id', 'int'),
            array('language_code', 'maxlen', 255),
        );
    }

}
