<?php

/**
 * Description of DbRelation
 *
 * @author user
 */
class DbRelation {
//	public $joinType = 'LEFT JOIN';
//	public $on = '';
//	public $alias = '';
	public $name = 'results';
	public $className = 'Result';
	public $foreignKey = '';
	public $select = '*';
	public $whereCondition = '';
	public $join = 'LEFT JOIN';
	
	public function __construct($name, $className, $foreignKey, $options = array()) {
		$this->name = $name;
		$this->className = $className;
		$this->foreignKey = $foreignKey;
		return true;
	}
}

?>
