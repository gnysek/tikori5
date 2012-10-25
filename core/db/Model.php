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

	private $_table;
	private $_fields;
	private $_values = array();
	private $_related = null;
	private $_primaryKey = 'id';
	private $_scopes = array();
	private $_relations = array();
	private $_isNewRecord = true;
	public $tableName = '';

	public function __construct() {
		$this->_scopes = $this->scopes();
		$this->_relations = $this->relations();
		$this->_fields = $this->getFields();
		$this->_table = $this->getTable();
	}

	public function getTable() {
		return '';
	}

	public static function model($model = __CLASS__) {
		return new $model();
	}

	/** find * */
	public function load($id) {
		return $this->find($id);
	}

	/*
	 * return $model
	 */

	public function find($id) {
		DB::query('SELECT * FROM ' . $this->_table . " WHERE " . $this->_primaryKey . ' = ' . mysql_real_escape_string($this->_id));
		return $this;
	}

	public function findByPK($key, $value) {
		return $this->findBy($this->_primaryKey, $value);
	}

	public function findBy($key, $value) {
		return $this;
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
		$getter = 'get' . ucfirst($value);
		if (isset($this->_values[$value])) {
			return $this->_values[$value];
		} else if (isset($this->_related[$value])) {
			return $this->_related[$value];
		} else if (in_array($value, $this->_relations)) {
			return $this->getRelated($value);
		} else if (method_exists($this, $getter)) {
			return $this->$getter();
		} else {
			return null;
		}
	}

	public function getRelated($relationName) {
		var_dump('getting related ' . $relationName);
		return null;
	}

}
