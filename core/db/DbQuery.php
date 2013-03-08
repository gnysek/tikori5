<?php

class DbQuery {

	const Q_SELECT = 1;
	const Q_UPDATE = 2;
	const Q_DELETE = 3;
	const Q_INSERT = 4;
	const Q_REPLACE = 5;
	const JOIN = 'JOIN'; // INNER JOIN
	const JOIN_LEFT = 'LEFT JOIN'; // LEFT OUTER JOIN
	const JOIN_RIGHT = 'RIGHT JOIN'; // RIGHT OUTER JOIN
	const JOIN_FULL = 'FULL JOIN'; // FULL OUTER JOIN

	public $alias = 't';
	private $_from = array();
	private $_fromAliases = array();
	private $_where = array();
	private $_order = array();
	private $_limit = 0;
	private $_offset = 0;
	private $_type = 1;
	private $_locked = false;
	private $_fields = array();
	private $_joinType = '';
	private $_joinTables = array();
	private $_joinOn = array();
	private $_fromInsteadJoin = false;
	private $_preparedSql = '';

	/**
	 * @return DbQuery
	 */
	public static function sql() {
		return new DbQuery();
	}

	public function execute() {
		return Core::app()->db->query($this->_parseSql());
	}

	public function __construct($from = '') {
		if (!empty($from)) {
			$this->from($from);
		}
		return $this;
	}

	public function from($from) {
		$this->_from = (is_array($from)) ? $from : array($this->alias => $from);
		$this->_resetAliases($this->_isAssoc($this->_from));
		return $this;
	}

	private function _resetAliases($assoc = false) {
//		$this->_fromAliases = array();
		foreach ($this->_from as $k => $field) {
			$this->_fromAliases[$field] = ($assoc) ? $k : $this->alias . $k;
		}
	}

	/**
	 * @return DbQuery
	 */
	public function select() {
		$this->_type = self::Q_SELECT;
		return $this;
	}

	/**
	 * @return DbQuery
	 */
	public function update() {
		$this->_type = self::Q_UPDATE;
		return $this;
	}

	/**
	 * @return DbQuery
	 */
	public function delete() {
		$this->_type = self::Q_DELETE;
		return $this;
	}

	/**
	 * @return DbQuery
	 */
	public function insert() {
		$this->_type = self::Q_INSERT;
		return $this;
	}

	public function replace() {
		$this->_type = self::Q_REPLACE;
		return $this;
	}

	public function where($where) {
		if (!is_array($where)) {
			$where = explode(' ', $where);
			if (count($where) != 3)
				throw new DbError('Wrong where param numbers.');
		}

		$this->_where[] = $where;
		return $this;
	}

	public function order($orderby) {
		return $this;
	}

	public function limit($limit = -1, $offset = 0) {
		if ($limit > 0) {
			$this->_limit = $limit;
			$this->_offset = $offset;
		}
		return $this;
	}

	public function joinOn($table, $on) {
//		$cnt = count($this->_joinTables);

		$this->_joinTables[] = $table;
		$this->_joinOn[$table] = $on;
		$this->_joinType = self::JOIN_LEFT;

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

	private function _parseSql($lock = false) {
		if ($this->_locked) {
			throw new DbError('This query was already executed and cannot be parsed anymore.');
		}

		$this->_locked = $lock;

		$sql = array();
		$fromAssoc = $this->_isAssoc($this->_from);

		// type
		switch ($this->_type) {
			case self::Q_UPDATE:
				$sql[] = 'UPDATE';
				break;
			case self::Q_REPLACE:
				$sql[] = 'REPLACE INTO';
				break;
			case self::Q_INSERT:
				$sql[] = 'INSERT INTO';
				break;
			case self::Q_DELETE:
				$sql[] = 'DELETE FROM';
				break;
			default:
				$sql[] = 'SELECT';
				if (empty($this->_fields)) {
					$fields = array();
					foreach ($this->_from as $val) {
						$fields[] = '`' . $this->_fromAliases[$val] . '`.*';
					}
					$sql[] = implode(', ', $fields);
				}
				$sql[] = 'FROM';
		}

		// from
		$from = array();
		foreach ($this->_from as $key => $val) {
			$from[] = '`' . $val . '` ' . (($this->_type == self::Q_SELECT) ? ' `' . $this->_fromAliases[$val] . '`' : '');
		}
		$sql[] = implode(', ', $from);

		// update ... set ...
		if ($this->_type == self::Q_UPDATE) {
			$sql[] = 'SET';
		}

		// left join
		if ($this->_type == self::Q_SELECT) {
			if (!empty($this->_joinTables)) {
				foreach ($this->_joinTables as $k => $table) {
					$sql[] = $this->_joinType;
					$sql[] = '`' . $table . '`.`' . $this->alias . ($k + 1) . '`';
					$sql[] = 'ON';
					$sql[] = '`' . $this->alias . ($k + 1) . '`.`' . $this->_joinOn[$table][0] . '`';
					$sql[] = $this->_joinOn[$table][1];
					$sql[] = '`' . $this->alias . '`.`' . $this->_joinOn[$table][2] . '`';
				}
			}
		}

		// values
		if (in_array($this->_type, array(self::Q_INSERT, self::Q_REPLACE, self::Q_UPDATE))) {
			// insert
			$fld = array();
			$val = array();
			$upd = array();

			if ($this->_isAssoc($this->_fields)) {
				foreach ($this->_fields as $fname => $fvalue) {
                    // remove those from where
//                    if ($this->_type == self::Q_UPDATE) {
//
//                    }
					$fld[] = '`' . $fname . '`';
					$val[] = is_string($fvalue) ? Core::app()->db->protect($fvalue) : $this->_nullify($fvalue);
					$upd[] = end($fld) . ' = ' . end($val);
				}
			} else {
				foreach ($this->_fields as $fvalue) {
					$val[] = is_string($fvalue) ? Core::app()->db->protect($fvalue) : $this->_nullify($fvalue);
				}
			}

			if ($this->_type == self::Q_INSERT) {
				if (!empty($fld)) {
					$sql[] = '(' . implode(', ', $fld) . ')';
				}
				$sql[] = 'VALUES (' . implode(', ', $val) . ')';
			} else {
				$sql[] = implode(', ', $upd);
			}
		}

		// where
		if (!in_array($this->_type, array(self::Q_INSERT, self::Q_REPLACE))) {
			if (!empty($this->_where)) {
				$sql[] = 'WHERE';
				$where = array();
//				var_dump($this->_where);
				foreach ($this->_where as $w) {
					$bld = '';
					if ($this->_type != self::Q_UPDATE) {
						$bld = '`' . $this->_fromAliases[(empty($w[3])) ? array_shift(array_values($this->_from)) : $w[3]] . '`.';
					}
					$bld .= '`' . $w[0] . '` ' . $w[1] . ' ' . (is_string($w[2]) ? Core::app()->db->protect($w[2]) : $this->_nullify($w[2]));
					$where[] = $bld;
				}
				$sql[] = implode(' AND ', $where);
			}

			if ($this->_limit > 0) {
				$sql[] = 'LIMIT ' . $this->_offset . ', ' . $this->_limit;
			}
		}

		$this->_preparedSql = implode(' ', $sql) . ';';

		return $this->_preparedSql;
	}

	private function _nullify($value = null) {
		return ($value === null) ? 'NULL' : $value;
	}

	public function __toString() {
		if (!$this->_locked) {
			$this->_parseSql();
		}

		return $this->_preparedSql;
	}

}
