<?php

class TDbTableSchema
{

    public $dbName;
    public $name;
    public $primaryKey = array();
    public $foreignKeys = array();
    /**
     * @var TDbColumnSchema[]
     */
    public $columns = array();

    /**
     * @param $name
     * @return null|TDbColumnSchema
     */
    public function getColumn($name)
    {
        return isset($this->columns[$name]) ? $this->columns[$name] : NULL;
    }

    public function getColumnNames()
    {
        return array_keys($this->columns);
    }

    public function setPrimaryKey($keys)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        foreach ($this->columns as $column) {
            $column->isPrimaryKey = false;
        }

        foreach ($keys as $key) {
            if ($column = $this->getColumn($key)) {
                $column->isPrimaryKey = false;
            } else {
                throw new Exception('Column ' . $key . ' cannot be primary, because there is no column like this in ' . $this->name);
            }
        }
    }
}
