<?php

/**
 * 
 */
class Model {

	const BELONGS_TO = 'BELONGS_TO';
	const HAS_ONE = 'HAS_ONE';
	const HAS_MANY = 'HAS_MANY';
//	const MANY_MANY = 4;
	const STATS = 'STATS';

	protected $_table;
	protected $_fields;
	protected $_values = array();
	protected $_related = null;
	protected $_primaryKey = 'id';
	protected $_scopes = array();
	protected $_relations = array();
	protected $_isNewRecord = true;
	public $tableName = '';

	public function __construct() {
		$this->_scopes = $this->scopes();
		$this->_relations = $this->relations();
		$this->_fields = $this->getFields();
		$this->_table = $this->getTable();
		$this->_primaryKey = $this->getPK();
	}

	public function getTable() {
		return '';
	}

	public function getPK() {
		return $this->_primaryKey;
	}

	public static function model($model = __CLASS__) {
		return new $model();
	}

	public function setValues($values) {
		if (is_array($values)) {
			$this->_values = $values;
		}
	}

	/** find * */
	public function load($id) {
		return $this->find($id);
	}

	/*
	 * return $model
	 */

	public function find($id) {
		if (!is_numeric($id)) {
			$id = DB::protect($id);
		}

		$sql = DbQuery::sql()->select()->from($this->_table)->where(array($this->_primaryKey, '=', $id));
		$result = $sql->execute();
		if (count($result) > 1)
			throw new DbError('Returned more than 1 record - PK wrongly defined?');
		foreach ($this->_fields as $field) {
			if ($result[0]->offsetExists($field)) {
				$this->_values[$field] = $result[0]->$field;
			}
		}
		return $this;
	}

	public function findByPK($value) {
		return $this->findBy($this->_primaryKey, $value);
	}

	public function findBy($key, $value) {
		if (!is_numeric($value)) {
			$value = DB::protect($value);
		}
		$sql = DbQuery::sql()->select()->from($this->_table)->where(array($key, '=', $value));
		$result = $sql->execute();
		$return = array();
		foreach ($result as $row) {
			/* @var $row Result */
			$c = self::model(get_called_class());
			$c->setValues($row->getIterator()->getArrayCopy());
			$return[] = $c;
		}
		return $return;
//		return $this;
	}

	public function findWhere($where) {
		return $this;
	}

	public function findAll() {
		return $this;
	}

	/**	 * */
	public function delete() {
		
	}

	public function deleteAll() {
		
	}

	public function deleteByPK($value) {
		return $this->delete($this->_primaryKey, $value);
	}

	public function deleteBy($key, $value) {
		
	}

	/**	 * */
	public function insert() {
		
	}

	public function update() {
		
	}

	public function save() {
		return ($this->_isNewRecord) ? $this->insert() : $this->save();
	}

	/**	 * */
	public function validate() {
		return true;
	}

	public function getErrors() {
		
	}

	public function addError($field, $error) {
		
	}

	public function addErrors($errors = array()) {
		
	}

	public function hasErrors() {
		
	}

	public function clearErrors() {
		
	}

	public function getFields() {
		
	}

	public function attributeLabels() {
		
	}

	public function getAttributeLabel($attribute) {
		$labels = $this->attributeLabels();
		if (isset($labels[$attribute]))
			return $labels[$attribute];
		else
			return $this->generateAttributeLabel($attribute);
	}

	/**
	 * (c) YiiFramework
	 * Generates a user friendly attribute label.
	 * This is done by replacing underscores or dashes with blanks and
	 * changing the first letter of each word to upper case.
	 * For example, 'department_name' or 'DepartmentName' becomes 'Department Name'.
	 * @param string $name the column name
	 * @return string the attribute label
	 */
	public function generateAttributeLabel($name) {
		return ucwords(trim(strtolower(str_replace(array('-', '_', '.'), ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)))));
	}

	public function getIsNewRecord() {
		return $this->_isNewRecord;
	}

	public function scopes() {
		
	}

	/**
	 * BELONGS_TO: e.g. a member belongs to a team;
	 * HAS_ONE: e.g. a member has at most one profile;
	 * HAS_MANY: e.g. a team has many members;
	 * MANY_MANY: e.g. a member has many skills and a skill belongs to a member.
	 * @return type
	 */
	public function relations() {
		return array();
	}

	public function __get($value) {
		/* if (__CLASS__ == 'Member') {
		  var_dump($value);
		  var_dump($this->_relations);
		  } */
		$getter = 'get' . ucfirst($value);
		if (isset($this->_values[$value])) {
			return $this->_values[$value];
		} else if (isset($this->_related[$value])) {
			return $this->_related[$value];
		} else if (array_key_exists($value, $this->_relations)) {
//			var_dump('relation ' . __CLASS__);
			return $this->getRelated($value);
		} else if (method_exists($this, $getter)) {
			return $this->$getter();
		} else {
			return null;
		}
	}

	public function getRelated($relationName) {
		switch ($this->_relations[$relationName][0]) {
			case self::HAS_MANY:
				$rel = self::model($this->_relations[$relationName][1]);
				$result = $rel->findBy($this->_relations[$relationName][2], $this->_values[$this->getPK()]);
				$this->_related[$relationName] = $result;
				return $this->_related[$relationName];
				break;
			case self::BELONGS_TO:
				$rel = self::model($this->_relations[$relationName][1]);
				$result = $rel->find($this->_values[$this->_relations[$relationName][2]]);
				$this->_related[$relationName] = $result;
//				var_dump($result);
				return $this->_related[$relationName];
				break;
			default:
				throw new DbError('Unknown relation type ' . $this->_relations[$relationName][0]);
		}
		return null;
	}

	public function __toString() {
		$head = '';
		$row = '';
		foreach ($this->_values as $k => $v) {
			$head .= ($k == $this->getPK()) ? '<th><u>' . $k . '</u></th>' : '<th>' . $k . '</th>';
			$row .= '<td>' . $v . '</td>';
		}

		$headerCount = count($this->_values);

		foreach ($this->_relations as $relationName => $relation) {
			///echo $relation[1];
			switch ($relation[0]) {
				case self::HAS_ONE:
					echo $relation[1];
					break;
				case self::BELONGS_TO:
					if (!empty($this->_related[$relationName])) {
						$head .= '<th rowspan="2" style="vertical-align: middle;">belongs to ' . $relation[1] . '</th>';
						$headerCount++;

						foreach ($this->_related[$relationName]->getFields() as $v) {
							$headerCount++;
							$head .= '<th>' . $v . '</th>';
							$row .= '<td>' . $this->_related[$relationName]->$v . '</td>';
						}
					}
					break;
			}
		}

		$head = '<th colspan="' . $headerCount . '">' . get_called_class() . '</th></tr><tr>' . $head;

		return /* 'Data in ' . get_called_class() . ' model instance:<br/>' . */ '<table><tr>' . $head . '</tr><tr>' . $row . '</tr></table>';
	}

}
