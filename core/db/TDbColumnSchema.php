<?php

class TDbColumnSchema
{

    public $name;
    /**
     * @var bool Is Primary Key
     */
    public $isPrimaryKey = false;
    public $allowNull = false;
    /**
     * @var string one of:
     * string, text, boolean, smallint, integer, bigint, float, decimal, datetime,
     * timestamp, time, date, binary, money
     */
    public $type;
    /**
     * @var string type in php for typecasting of:
     * string, boolean, integer, double
     */
    public $phpType;
    /**
     * @var string original type in database
     */
    public $dbType;
    public $defaultValue;
    public $enumValues;
    public $size;

    public $precision;
    public $scale;
    public $autoIncrement = false;
    public $unsigned = false;
    public $comment;

    /**
     * Converts the input value according to [[phpType]] after retrieval from the database.
     * If the value is null or an [[Expression]], it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value
     */
    public function typecast($value)
    {
        if ($value === '' && $this->type !== TDbSchema::TYPE_TEXT && $this->type !== TDbSchema::TYPE_STRING && $this->type !== TDbSchema::TYPE_BINARY) {
            return null;
        }
        if ($value === null || gettype($value) === $this->phpType) {
            return $value;
        }
        switch ($this->phpType) {
            case 'resource':
            case 'string':
                return is_resource($value) ? $value : (string)$value;
            case 'integer':
                return (int)$value;
            case 'boolean':
                return (bool)$value;
            case 'double':
                return (double)$value;
        }

        return $value;
    }
}
