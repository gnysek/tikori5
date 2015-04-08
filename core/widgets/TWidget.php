<?php

class TWidget extends TView
{

    public function run()
    {
        return NULL;
    }

    public function init()
    {

    }

    public function setupProperties($properties)
    {
        foreach ($properties as $k => $v) {
            if (isset($this->$k)) {
                $this->$k = $v;
            }
        }

        return $this;
    }

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
