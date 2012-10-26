<?php

/**
 * Description of Member
 *
 * @property $id id
 * @property $name name
 * @property $team team
 * @author user
 */
class Member extends Model {
	//put your code here
	
	public function getTable(){
		return 'test_user';
	}
}

?>
