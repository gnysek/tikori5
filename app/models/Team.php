<?php

/**
 * Description of Team
 *
 * @author user
 * @property Member $users Users that Team have
 */
class Team extends Model
{

    /**
     * @param null|string $model
     *
     * @return Team
     */
    public static function model($model = __CLASS__)
    {
        return parent::model($model);
    }

    public function relations()
    {
        return array(
            'users' => array(self::HAS_MANY, 'Member', 'team'),
        );
    }

    public function getFields()
    {
        return array(
            'team_id', 'team_name'
        );
    }

    public function getTable()
    {
        return 'test_team';
    }

    public function getPK()
    {
        return $this->_fields[0];
    }

}

?>
