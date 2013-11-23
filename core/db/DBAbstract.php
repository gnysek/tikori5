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
    protected $_tableInfos = array();

    /**
     * @var PDO
     */
    protected $_conn = FALSE;

    abstract public function connect();

    abstract public function query($sql, $skip = '', $assoc = TRUE);

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
        return $this->conn();
    }

    public function getTableInfo($table)
    {
        if (!array_key_exists($table, $this->_tableInfos)) {
            $infos = array();
            $data = $this->query("SHOW COLUMNS IN `{$table}");
            foreach ($data as $row) {
                /* @var Record $row */
                $infos[$row->Field] = new Record($row->getData(), true);
            }
            $this->_tableInfos[$table] = new Record($infos, true);
        }
        return $this->_tableInfos[$table];
    }
}
