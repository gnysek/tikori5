<?php

/**
 * Description of Member
 *
 * @property $id   id
 * @property $name name
 * @property $team team
 * @proprrty $team0 Team
 * @author user
 */
class Member extends Model
{

    //put your code here

    public function getFields()
    {
        return array('id', 'name', 'team');
    }

    public function relations()
    {
        return array(
            'team0' => array(self::BELONGS_TO, 'Team', 'team'),
        );
    }

    public function getTable()
    {
        return 'test_user';
    }

}

?>
