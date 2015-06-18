<?php

/**
 * Description of Error
 *
 * @author user
 */
class DbError extends Exception
{

    protected $_mysqlError = 0;

    public function __construct($message = "", $mysqlError = 0, $code = 0, Exception $previous = null)
    {
        $this->_mysqlError = intval($mysqlError);
        parent::__construct($message, $code, $previous);
    }

    public function getMysqlError()
    {
        return $this->_mysqlError;
    }

}
