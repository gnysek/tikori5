<?php

class DbQuery {

	const Q_SELECT = 1;
	const Q_UPDATE = 2;
	const Q_DELETE = 3;
	const Q_INSERT = 4;
	const Q_REPLACE = 5;

	private $_from = '';
	public $alias = 't';
	private $_where = array();
	private $_order = array();
	private $_limit = 0;
	private $_offset = 0;
	private $_type = 1;
	private $_locked = false;
	private $_fields = array();
	private $_preparedSql = '';

	public static function query() {
		return new DbQuery();
	}

	public function __construct($from = '') {
		if (!empty($from)) {
			$this->from($from);
		}
		return $this;
	}

	public function from($from) {
		$this->_from = $from;
		return $this;
	}

	public function select() {
		$this->_type = self::Q_SELECT;
		return $this;
	}

	public function update() {
		$this->_type = self::Q_UPDATE;
		return $this;
	}

	public function delete() {
		$this->_type = self::Q_DELETE;
		return $this;
	}

	public function insert() {
		$this->_type = self::Q_INSERT;
		return $this;
	}

	public function replace() {
		$this->_type = self::Q_REPLACE;
		return $this;
	}

	public function where($where) {
		$this->_where = array($where);
		return $this;
	}

	public function fields($fields) {
		$this->_fields = $fields;
		return $this;
	}

	public function whereAnd($where) {
		return $this;
	}

	public function whereOr($where) {
		return $this;
	}

	private function _isAssoc($array) {
		return (bool) count(array_filter(array_keys($array), 'is_string'));
	}

	private function _parseSql() {
		if ($this->_locked) {
			throw new DbError('This query was already executed and cannot be parsed anymore.');
		}
		
		$sql = array();

		// type
		switch ($this->_type) {
			case self::Q_UPDATE: $sql[] = 'UPDATE';
				break;
			case self::Q_REPLACE: $sql[] = 'REPLACE INTO';
				break;
			case self::Q_INSERT: $sql[] = 'INSERT INTO';
				break;
			case self::Q_DELETE: $sql[] = 'DELETE FROM';
				break;
			default: $sql[] = 'SELECT';
				if (empty($this->_fields)) {
					$sql[] = '`' . $this->alias . '`.*';
				}
				$sql[] = 'FROM';
		}

		// from
		$sql[] = '`' . $this->_from . '`';

		// update ... set ...
		if ($this->_type == self::Q_UPDATE) {
			$sql[] = 'SET';
		}

		if (!in_array($this->_type, array(self::Q_INSERT, self::Q_REPLACE))) {
			if (!empty($this->_where)) {
				$sql[] = 'WHERE';
				$sql[] = implode(' AND ', $this->_where);
			}
		} else {
			$fld = array();
			$val = array();

			if ($this->_isAssoc($this->_fields)) {
				foreach ($this->_fields as $fname => $fvalue) {
					$fld[] = '`' . $fname . '`';
					$val[] = is_string($fvalue) ? DB::protect($fvalue) : intval($fvalue);
				}
			} else {
				foreach ($this->_fields as $fvalue) {
					$val[] = is_string($fvalue) ? DB::protect($fvalue) : intval($fvalue);
				}
			}

			if (!empty($fld)) {
				$sql[] = '(' . implode(', ', $fld) . ')';
			}
			$sql[] = 'VALUES (' . implode(', ', $val) . ')';
		}

		$this->_preparedSql = implode(' ', $sql) . ';';
	}

	public function __toString() {
		if (!$this->_locked) {
			$this->_parseSql();
		}

		return $this->_preparedSql;
	}

}