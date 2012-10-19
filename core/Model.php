<?php

class Model {

	private $_table;
	private $_fields;
	private $_primaryKey = 'id';
	private $_scopes = array();
	private $_relations = array();
	private $_isNewRecord = true;
	public $tableName = '';

	public function __construct() {
		$this->_scopes = $this->_scopes();
		$this->$_relations = $this->_relations();
	}

	public static function model($model = __CLASS__) {
		return new $model();
	}

	/** find * */
	public function load($id) {
		return $this->find($id);
	}

	public function find($id) {
		
	}

	public function findByPK($key, $value) {
		return $this->findBy($this->_primaryKey, $value);
	}

	public function findBy($key, $value) {
		
	}

	public function findWhere($where) {
		
	}

	public function findAll() {
		
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

	/**	 * */
	public function scope() {
		
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

	private function _scopes() {
		return array();
	}

	private function _relations() {
		return array();
	}

}
