<?php

class TDbSchema
{

    const TYPE_PK = 'pk';
    const TYPE_BIGPK = 'bigpk';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIGINT = 'bigint';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';
    const TYPE_BINARY = 'binary';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_MONEY = 'money';

    const CACHE_SCHEMA = '__TBSCHEMA__';

    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
    public $typeMap = array(
        'tinyint'    => self::TYPE_SMALLINT,
        'bit'        => self::TYPE_SMALLINT,
        'smallint'   => self::TYPE_SMALLINT,
        'mediumint'  => self::TYPE_INTEGER,
        'int'        => self::TYPE_INTEGER,
        'integer'    => self::TYPE_INTEGER,
        'bigint'     => self::TYPE_BIGINT,
        'float'      => self::TYPE_FLOAT,
        'double'     => self::TYPE_FLOAT,
        'real'       => self::TYPE_FLOAT,
        'decimal'    => self::TYPE_DECIMAL,
        'numeric'    => self::TYPE_DECIMAL,
        'tinytext'   => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext'   => self::TYPE_TEXT,
        'text'       => self::TYPE_TEXT,
        'varchar'    => self::TYPE_STRING,
        'string'     => self::TYPE_STRING,
        'char'       => self::TYPE_STRING,
        'datetime'   => self::TYPE_DATETIME,
        'year'       => self::TYPE_DATE,
        'date'       => self::TYPE_DATE,
        'time'       => self::TYPE_TIME,
        'timestamp'  => self::TYPE_TIMESTAMP,
        'enum'       => self::TYPE_STRING,
    );

    /**
     * @var TDbTableSchema[]
     */
    private $_tables = array();

    /**
     * @param $name
     * @param bool $refresh
     * @return null|TDbTableSchema
     * @throws Exception
     */
    public function getTableSchema($name, $refresh = false)
    {
        //TODO: refresh should also refresh cache, not only reload
        if (isset($this->_tables[$name]) && $refresh === false) {
            return $this->_tables[$name];
        }

        return $this->loadTableSchema($name, $refresh === true);
    }

    /**
     * @param $name
     * @param bool $forceRefresh
     * @return null|TDbTableSchema
     * @throws DbError
     */
    public function loadTableSchema($name, $forceRefresh = false)
    {
        Profiler::addLog('GETTING TABLE INFO v2 ' . $name, 1);

        $table = new TDbTableSchema();
        $this->_resolveTableNames($table, $name);

        $cached = Core::app()->cache->loadCacheIfFresh(self::CACHE_SCHEMA . $table->name, CACHE::HOUR, array()); // one hour
        if (!empty($cached) and $forceRefresh === false) {
            Profiler::addNotice('Loaded ' . $name . ' schema');
            list($columns, $pk, $foreign) = unserialize($cached);
            $table->columns = $columns;
            $table->primaryKey = $pk;
            $table->foreignKeys = $foreign;
            return $this->_tables[$name] = $table;
        } else {
            if ($this->findColumns($table)) {
                Profiler::addNotice('Refreshed ' . $name . ' schema');
                $this->findConstraints($table);
                $this->_cacheSchema($table);
                return $this->_tables[$name] = $table;
            }

            throw new \DbError(sprintf('Can\'t load <kbd>`%s`</kbd> table schema info. Table not exists.', $name));
        }
    }

    /**
     * @param $table TdbTableSchema
     */
    private function _cacheSchema($table) {
        Core::app()->cache->saveCache(self::CACHE_SCHEMA . $table->name, serialize(array(
            $table->columns,
            $table->primaryKey,
            $table->foreignKeys
        )));
    }

    public function clearSchemaCache() {
        CacheableBlock::clearByTags(array(self::CACHE_SCHEMA));
        $this->_tables = array();
    }

    public function getTableNames()
    {
        return array_keys($this->_tables);
    }

    /**
     * @param $table
     * @return bool
     * @throws Exception
     */
    public function hasTable($table) {
        return $this->getTableSchema($table) === null ? false : true;
    }

    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        } else {
            //TODO: PDO quote
            //return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
            return $str;
        }
    }

    public function quoteTableName($name)
    {
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        return implode('.', $parts);
    }

    public function quoteSimpleTableName($name)
    {
        return strpos($name, "`") !== false ? $name : "`" . $name . "`";
    }

    public function quoteColumnName($name)
    {
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }
        return $prefix . $this->quoteSimpleColumnName($name);
    }

    public function quoteSimpleColumnName($name)
    {
        return (strpos($name, '`') !== false || $name === '*') ? $name : '`' . $name . '`';
    }

    /**
     * @param $table TdbTableSchema
     * @param $name  string
     */
    private function _resolveTableNames($table, $name)
    {
        $name = str_replace('`', '', $name);

        $table->dbName = $name;
        $table->name = $name; //TODO: add prefix
    }

    /**
     * @param $table TdbTableSchema
     * @throws Exception
     * @return bool
     */
    private function findColumns($table)
    {
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->quoteSimpleTableName($table->dbName);

        try {
            $columns = Core::app()->db->query($sql);
        } catch (DbError $e) {
            if (preg_match('/42S02/', $e->getMessage()) or $e->getMysqlError() == 1146) {
                return false;
            }
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }

        foreach ($columns as $info) {
            $column = $this->_loadColumnSchema($info);
            $table->columns[$column->name] = $column;
            if ($column->isPrimaryKey) {
                $table->primaryKey[] = $column->name;
            }
        }
        return true;
    }

    /**
     * @param $info
     * @return TDbColumnSchema
     */
    private function _loadColumnSchema($info)
    {
        $column = new TDbColumnSchema;

        $column->name = $info['Field'];
        $column->allowNull = $info['Null'] === 'YES';
        $column->isPrimaryKey = strpos($info['Key'], 'PRI') !== false;
        $column->autoIncrement = stripos($info['Extra'], 'auto_increment') !== false;
        $column->comment = $info['Comment'];


        $column->dbType = $info['Type'];
        $column->unsigned = strpos($column->dbType, 'unsigned') !== false;

        $column->type = self::TYPE_STRING;
        if (preg_match('/^(\w+)(?:\(([^\)]+)\))?/', $column->dbType, $matches)) {
            $type = $matches[1];
            if (isset($this->typeMap[$type])) {
                $column->type = $this->typeMap[$type];
            }
            if (!empty($matches[2])) {
                if ($type === 'enum') {
                    $values = explode(',', $matches[2]);
                    foreach ($values as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }
                    $column->enumValues = $values;
                } else {
                    $values = explode(',', $matches[2]);
                    $column->size = $column->precision = (int)$values[0];
                    if (isset($values[1])) {
                        $column->scale = (int)$values[1];
                    }
                    if ($column->size === 1 && $type === 'bit') {
                        $column->type = 'boolean';
                    } elseif ($type === 'bit') {
                        if ($column->size > 32) {
                            $column->type = 'bigint';
                        } elseif ($column->size === 32) {
                            $column->type = 'integer';
                        }
                    }
                }
            }
        }

        $column->phpType = $this->getColumnPhpType($column);

        if ($column->type !== 'timestamp' || $info['Default'] !== 'CURRENT_TIMESTAMP') {
            $column->defaultValue = $column->typecast($info['Default']);
        }

        return $column;
    }

    /**
     * Extracts the PHP type from abstract DB type.
     * @param TDbColumnSchema $column the column schema information
     * @return string PHP type name
     */
    protected function getColumnPhpType($column)
    {
        static $typeMap = array(
            // abstract type => php type
            'smallint' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            'boolean' => 'boolean',
            'float' => 'double',
            'double' => 'double',
            'binary' => 'resource', //TODO: check it's compatible
        );
        if (isset($typeMap[$column->type])) {
            if ($column->type === 'bigint') {
                return PHP_INT_SIZE === 8 && !$column->unsigned ? 'integer' : 'string';
            } elseif ($column->type === 'integer') {
                return PHP_INT_SIZE === 4 && $column->unsigned ? 'string' : 'integer';
            } else {
                return $typeMap[$column->type];
            }
        } else {
            return 'string';
        }
    }

    public function getColumnSimplifiedType($type)
    {
        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        }

        return $type;
    }

    /**
     * @param $table TDbTableSchema
     */
    private function findConstraints($table)
    {
        $sql = $this->getCreateTableSql($table);
        $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fks = array_map('trim', explode(',', str_replace('`', '', $match[1])));
                $pks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                $constraint = array(str_replace('`', '', $match[2]));
                foreach ($fks as $k => $name) {
                    $constraint[$name] = $pks[$k];
                }
                $table->foreignKeys[] = $constraint;
            }
        }
    }

	/**
	 * @param $table TDbTableSchema
	 * @return string
	 */
    private function getCreateTableSql($table)
    {
        $sql = 'SHOW CREATE TABLE ' . $this->quoteSimpleTableName($table->dbName);
        $row = Core::app()->db->queryOne($sql);
        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }
        return $sql;
    }

}
