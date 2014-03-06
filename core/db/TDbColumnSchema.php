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
}
