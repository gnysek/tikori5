<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 08.03.13
 * Time: 13:38
 * To change this template use File | Settings | File Templates.
 */
abstract class DbAbstract
{

    protected $_init = FALSE;
    protected $_queries = 0;
    protected $_queryList = array();
	/** @var TDbSchema */
	protected $_schema = null;

    /**
     * @var PDO
     */
    protected $_conn = FALSE;

    protected $_config = array();

    public function __construct($cfg = array())
    {
        if (!empty($cfg)) {
            $this->_config = $cfg;
        }
    }

    abstract public function connect();

    /**
     *
     * @param string $sql   Sql query to perform
     * @param string $skip  Not used for now
     * @param bool   $assoc Assoc or not?
     *
     * @throws DbError
     * @return Record|Record[]
     */
    abstract public function query($sql, $skip = '', $assoc = TRUE);

    /**
     * @param        $sql
     * @param string $skip
     * @param bool $assoc
     * @return Result|bool|array
     * @throws DbError
     */
    public function queryOne($sql, $skip = '', $assoc = TRUE) {
        $result = $this->query($sql, $skip, $assoc);
        if (count($result)) {
            return $result[0];
        }
        return false;
    }

    abstract public function update($sql);

    abstract public function lastId();

    abstract public function protect($string);

    public function conn()
    {
        if (!$this->_init) {
            $this->connect();
        }

        return $this->_conn;
    }

    public function connected()
    {
        return (bool)$this->conn();
    }

    public function queries()
    {
        return $this->_queries;
    }

    public function sqlQueries()
    {
        return $this->_queryList;
    }

    public function sqlQueryLast()
    {
        return $this->_queryList[count($this->_queryList) - 1];
    }

    public function close()
    {
        $this->_conn = NULL;
    }

    /**
     * @return Sql
     */
    public static function sql()
    {
        return new Sql();
    }

    public function isInitialized()
    {
        return $this->_init;
    }

    public function init()
    {
		$this->_schema = new TDbSchema();
        return $this->conn();
    }

    /**
     * @param $table
     * @return TDbTableSchema
     * @throws Exception
     */
    public function getTableInfo($table)
    {
        return $this->getSchema()->getTableSchema($table);
    }

    public function getSchema() {
        return $this->_schema;
    }

    public function hasTableColumn($table, $column) {
        return $this->getTableInfo($table)->getColumn($column) ? true : false;
    }

    public function hasTable($table)
    {
        return $this->getSchema()->hasTable($table);
    }

    public function getTableColumnType($table, $column){
        //TODO: duplicates with \DbQuery::_formatAgainstType()
        if ($table = $this->getTableInfo($table)->getColumn($column)) {
            /* @var $table TDbColumnSchema */
            return $table->type;
        }
        return false;
    }

    public function getTableColumnDefaultValue($table, $column){
        //TODO: duplicates with \DbQuery::_formatAgainstType()
        if ($table = $this->getTableInfo($table)->getColumn($column)) {
            /* @var $table TDbColumnSchema */
            return $table->defaultValue;
        }
        return false;
    }

    public function isTableColumnWithoutAnyDefaultValue($table, $column)
    {
        if ($table = $this->getTableInfo($table)->getColumn($column)) {
            /* @var $table TDbColumnSchema */
            return $table->defaultValue === null and $table->allowNull === false;
        }
        return false;
    }
}
