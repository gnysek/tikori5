<?php

class Widget extends TWidget
{

    private $_data = array();

    public function __get($key)
    {
        return (array_key_exists($key, $this->_data)) ? $this->_data[$key] : NULL;
    }

    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }
}
