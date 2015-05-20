<?php

namespace Core\Common;

class DefaultObject implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * Keeps properties of this object
     *
     * @var array
     */
    protected $_data = array();

    function __construct($data = array())
    {
        if (is_array($data)) {
            $this->replace($data);
        }
    }


    /**
     * Normalize data key
     *
     * Used to transform data key into the necessary key format for this set.
     *
     * @param  string $key The data key
     * @return mixed       The transformed/normalized data key
     */
    protected function _normalizeKey($key)
    {
        return $key;
    }

    public function keys()
    {
        return array_keys($this->_data);
    }

    public function has($key)
    {
        return (array_key_exists($this->_normalizeKey($key), $this->_data));
    }

    public function get($key, $default = null)
    {
        return ($this->offsetExists($key)) ? $this->_data[$this->_normalizeKey($key)] : $default;
    }

    public function all()
    {
        return $this->_data;
    }

    public function set($key, $value)
    {
        if (is_array($value)) {
            $cname = __CLASS__;
            $value = new $cname($value);
        }
        $this->_data[$this->_normalizeKey($key)] = $value;
    }

    public function remove($key)
    {
        unset($this->_data[$this->_normalizeKey($key)]);
    }

    public function replace($array = array())
    {
        foreach ($array as $k => $v) {
            $this->set($k, $v);
        }
    }

    public function clear()
    {
        $this->_data = array();
    }

    /*
     * Magic methods
     */

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __unset($key)
    {
        $this->unset($key);
    }

    function __call($name, $arguments)
    {
        switch (substr($name, 0, 3)) {
            case 'set':
                if (count($arguments) == 1) {
                    $this->set(substr($name, 3), $arguments[0]);
                }
                break;
            case 'get':
                return $this->get(substr($name, 3));
                break;
            case 'has':
                return $this->get(substr($name, 3));
                break;
        }

        return null;
    }


    /*
     * Array Access
     */

    public function offsetExists($key)
    {
        return $this->has($key);
    }


    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /*
     * ArrayIterator
     */

    public function getIterator()
    {
        return new \ArrayIterator($this->_data);
    }

    /*
     * Countable
     */

    /**
     * Count elements of an object
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->_data);
    }
}
