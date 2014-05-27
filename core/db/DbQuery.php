<?php

class DbQuery
{

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
    private $_group = array();
    private $_limit = -1;
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
    public static function sql()
    {
        return new DbQuery();
    }

    public function execute()
    {
        return Core::app()->db->query($this->_parseSql());
    }

    public function __construct($from = '')
    {
        if (!empty($from)) {
            $this->from($from);
        }
        return $this;
    }

    /**
     * @param $from
     *
     * @return DbQuery
     */
    public function from($from)
    {
        $this->_from = (is_array($from)) ? $from : array($this->alias => $from);
        $this->_resetAliases($this->_isAssoc($this->_from));
        return $this;
    }

    /**
     * Same as from() but this will force type to Q_INSERT
     *
     * @see from
     *
     * @param $into
     *
     * @return DbQuery
     */
    public function into($into)
    {
        $this->_type = self::Q_INSERT;
        return $this->from($into);
    }

    private function _resetAliases($assoc = false)
    {
//		$this->_fromAliases = array();
        foreach ($this->_from as $k => $field) {
            $this->_fromAliases[$field] = ($assoc) ? $k : $this->alias . $k;
        }
    }

    /**
     * @param null $fields
     *
     * @return DbQuery
     */
    public function select($fields = NULL)
    {
        if (!empty($fields)) {
            $this->fields($fields);
        }
        $this->_type = self::Q_SELECT;
        return $this;
    }

    /**
     * @return DbQuery
     */
    public function update()
    {
        $this->_type = self::Q_UPDATE;
        return $this;
    }

    /**
     * @return DbQuery
     */
    public function delete()
    {
        $this->_type = self::Q_DELETE;
        return $this;
    }

    /**
     * @return DbQuery
     */
    public function insert()
    {
        $this->_type = self::Q_INSERT;
        return $this;
    }

    public function replace()
    {
        $this->_type = self::Q_REPLACE;
        return $this;
    }

    /**
     * @param $where
     *
     * @return DbQuery
     * @throws DbError
     */
    public function where($where)
    {
        if (!is_array($where)) {
            $where = explode(' ', $where);
            if (count($where) != 3) {
                throw new DbError('Wrong where param numbers.');
            }
        }

        if (is_array($where[0])) {
            // multiarrays
            foreach ($where as $subWhere) {
                $this->_where[] = $subWhere;
            }
        } else {
            $this->_where[] = $where;
        }
        return $this;
    }

    public function order($orderby)
    {
        $this->_order = $orderby;
        return $this;
    }

    public function group($groupby)
    {
        $this->_group = $groupby;
        return $this;
    }

    public function limit($limit = -1, $offset = 0)
    {
        if ($limit > -1) {
            $this->_limit = $limit;
            $this->_offset = $offset;
        }
        return $this;
    }

    public function conditions(array $conditions)
    {
        foreach ($conditions as $conditionName => $conditionValue) {
            switch ($conditionName) {
                case 'order':
                    $this->order($conditionValue);
                    break;
                case 'where':
                    $this->where($conditionValue);
                    break;
            }
        }

        return $this;
    }

    public function joinOn($table, $on)
    {
//		$cnt = count($this->_joinTables);

        $this->_joinTables[] = $table;
        $this->_joinOn[$table] = $on;
        $this->_joinType = self::JOIN_LEFT;

        return $this;
    }

    public function fields($fields)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        $this->_fields = $fields;
        return $this;
    }

    public function whereAnd($where)
    {
        return $this;
    }

    public function whereOr($where)
    {
        return $this;
    }

    private function _isAssoc($array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    private function _parseSql($lock = false)
    {
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
                } else {
                    $fields = array();
                    foreach ($this->_fields as $field) {
                        //TODO: aliases
                        $fields[] = $field;
                    }
                    $sql[] = implode(',', $fields);
                }
                $sql[] = "\nFROM";
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
                    $sql[] = "\n" . $this->_joinType;
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

            reset($this->_from);

            if ($this->_isAssoc($this->_fields)) {
                foreach ($this->_fields as $fname => $fvalue) {
                    // remove those from where
//                    if ($this->_type == self::Q_UPDATE) {
//
//                    }
                    $fld[] = '`' . $fname . '`';
                    //$val[] = is_string($fvalue) ? Core::app()->db->protect($fvalue) : $this->_nullify($fvalue);
                    $val[] = $this->_formatAgainstType($this->_from[key($this->_from)], $fname, $fvalue);
                    $upd[] = end($fld) . ' = ' . end($val);
                }
            } else {
                $fields = array_keys((array)$this->getTableInfo($this->_from[key($this->_from)]));

                foreach ($this->_fields as $key => $fvalue) {
                    //$val[] = is_string($fvalue) ? Core::app()->db->protect($fvalue) : $this->_nullify($fvalue);
                    $val[] = $afterCondition = $this->_formatAgainstType($this->_from[key($this->_from)], $fields[$key], $fvalue);
                }

                unset($fields);
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

        // where: not for INSERT / REPLACE
        reset($this->_from);
        if (!in_array($this->_type, array(self::Q_INSERT, self::Q_REPLACE))) {
            if (!empty($this->_where)) {
                $sql[] = 'WHERE';
                $where = array();
//				var_dump($this->_where);
                foreach ($this->_where as $w) {

                    // additional fourth param to use WHEREs with () - idea removed
                    /*if (count($w) == 4 && $w[3] === true) {
                        $where[] = $w[0] . ' ' . $w[1] . ' ' . $w[2];
                        continue;
                    }*/

                    $bld = '';
                    if ($this->_type != self::Q_UPDATE) {
                        $bld
                            = '`' . $this->_fromAliases[(empty($w[3]))
                                ? $this->_from[key($this->_from)]
                                : $w[3]]
                            . '`.';
                    }

                    // TODO: better checking...
                    //$afterCondition = ((is_string($w[2]) or is_array($w[2])) ? Core::app()->db->protect($w[2]) : $this->_nullify($w[2]));
                    $afterCondition = $this->_formatAgainstType($this->_from[key($this->_from)], $w[0], $w[2]);

                    if ($w[1] == 'IN') {
                        $afterCondition = '(' . $afterCondition . ')';
                    }

                    if (count($w) == 5) {
                        $bld = sprintf($w[4], $bld . '`' . $w[0] . '` ');
                    } else {
                        $bld .= '`' . $w[0] . '` ';
                    }
                    $bld .= $w[1] . ' ' . $afterCondition;
                    $where[] = $bld;
                }
                $sql[] = implode(' AND ', $where);
            }

            // group

            if (!empty($this->_group)) {
                $sql[] = 'GROUP BY ' . /*implode(', ', */
                    $this->_group; //);
            }

            // order
            if (!empty($this->_order)) {
                $sql[] = 'ORDER BY ' . /*implode(', ', */
                    $this->_order; //);
            }

            // limit
            if ($this->_limit > -1) {
                $sql[] = 'LIMIT ' . $this->_limit . ', ' . $this->_offset;
            }
        }

        $this->_preparedSql = implode(' ', $sql) . ';';

        return $this->_preparedSql;
    }

    private function _formatAgainstType($table, $field, $value)
    {
        if (is_array($value)) {
            $collect = array();
            foreach ($value as $val) {
                $collect[] = $this->_formatAgainstType($table, $field, $val);
            }

            return implode(', ', $collect);
        }

        $tableInfo = $this->getTableInfo($table);

        if (!$tableInfo->offsetExists($field)) {
            throw new DbError('Table ' . $table . ' don\'t have field ' . $field . '. Only ' . implode(', ', $tableInfo->getFields()));
        }

        if ($tableInfo->$field->Null == 'YES' && $value === NULL) {
            return 'NULL';
        }

        if ($value === NULL && $tableInfo->$field->Default !== NULL) {
            $value = $tableInfo->$field->Default;
        }

        if (preg_match('/int/', $tableInfo->$field->Type)) {
            return intval($value);
        }
        if (preg_match('/double/', $tableInfo->$field->Type)) {
            return doubleval($value);
        }
        if (preg_match('/float/', $tableInfo->$field->Type)) {
            return doubleval($value);
        }
        if (preg_match('/decimal/', $tableInfo->$field->Type)) {
            return preg_replace('[^0-9\.]', '', str_replace(',', '.', $value));
        }

        return Core::app()->db->protect($value);
    }

    private function _nullify($value = NULL)
    {
        return ($value === NULL) ? 'NULL' : $value;
    }

    public function __toString()
    {
        if (!$this->_locked) {
            $this->_parseSql();
        }

        return $this->_preparedSql;
    }

    /**
     * @param $table
     *
     * @return Record|null
     */
    public function getTableInfo($table)
    {
        //TODO: force to not change
        if (Core::app()->db) {
            return Core::app()->db->getTableInfo($table);
        }
        return NULL;
    }

}
