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

    abstract public function query($sql, $skip = '', $assoc = TRUE);

    /**
     * @param        $sql
     * @param string $skip
     * @param bool   $assoc
     * @return Result|bool|array
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
        return null;
    }
}
