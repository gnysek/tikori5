<?php

/**
 * Description of Content
 *
 * @author gnysek
 * @property int     $id
 * @property string  $name
 * @property boolean $enabled
 * @property string  $path
 * @property int     $parent
 * @property int     $type
 * @property int     $created
 * @property int     $updated
 * @property int     $comments
 * @property int     $author
 * @proprety ContentTranslations content_translations
 */
class Content extends Model
{

//	protected $_pkId = 'id';

    /**
     * @param string $model
     *
     * @return Content|Model
     */
    public static function model($model = __CLASS__)
    {
        return parent::model($model);
    }

    public function getTable()
    {
        return 'content';
    }

    public function relations()
    {
        return array(
            'content_translations' => array(self::HAS_MANY, 'ContentTranslations', 'content_id'),
        );
    }

    public function getFields()
    {
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

    public function rules()
    {
        return array(
            array(array('name', 'enabled', 'path', 'type', 'created', 'updated', 'comments', 'author'), 'required'),
            array(array('id', 'enabled', 'parent', 'type', 'created', 'updated', 'comments', 'author'), 'int'),
            array('name', 'maxlen', 'len' => 255),
        );
    }

    protected function _beforeSave()
    {
        $time = time();
        if ($this->_isNewRecord) {
            $this->created = $time;
        }
        $this->updated = $time;
    }


    public function getChildrenCount()
    {
        $result = DbQuery::sql()->select('COUNT(*) AS total')->from($this->_table)->where(array('parent', '=', $this->id))->execute();
        //TODO: return as one Record, not as array?
        return $result[0]->total;
    }
}

?>
