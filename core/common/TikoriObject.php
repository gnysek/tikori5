<?php

namespace Core\Common;

class TikoriObject implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * Keeps properties of this object
     *
     * @var array
     */
    protected $_data = array();

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
        // TODO: Implement keys() method.
    }

    public function has()
    {
        // TODO: Implement has() method.
    }

    public function get($key, $default = null)
    {
        return ($this->offsetExists($key)) ? $this->_data[$this->_normalizeKey($key)] : $default;
    }

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function set($key, $value)
    {
        // TODO: Implement set() method.
    }

    public function remove($key)
    {
        // TODO: Implement remove() method.
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    /*
     * Magic methods
     */

    public function __isset($key)
    {
        // TODO: Implement __isset() method.
    }

    public function __get($key)
    {
        // TODO: Implement __get() method.
    }

    function __set($name, $value)
    {
        // TODO: Implement __set() method.
    }

    function __unset($name)
    {
        // TODO: Implement __unset() method.
    }

    /*
     * Array Access
     */

    public function offsetExists($key)
    {
        return (array_key_exists($this->_normalizeKey($key), $this->_data));
    }


    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->_data[$this->_normalizeKey($key)] = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->_data[$this->_normalizeKey($key)]);
    }

    /*
     * ArrayIterator
     */

    public function getIterator()
    {
        return new ArrayIterator($this->_data);
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
        // TODO: Implement count() method.
    }
}
