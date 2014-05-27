<?php
/**
 * @copyright   Copyright (c) 2011, Flight Framework, Mike Cao <mike@mikecao.com>
 * @license     MIT, http://flightphp.com/license
 */

/**
 * The Collection class allows you to access a set of data
 * using both array and object notation.
 */
class Collection implements ArrayAccess, Iterator, Countable
{

    /**
     * Collection data.
     *
     * @var array|Model[]
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param array $data Initial data
     */
    public function __construct(array $data = array())
    {
        $this->_data = $data;
    }

    /**
     * Gets an item.
     *
     * @param string $key Key
     *
     * @return mixed Value
     */
    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : NULL;
    }

    /**
     * Set an item.
     *
     * @param string $key   Key
     * @param mixed  $value Value
     */
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Checks if an item exists.
     *
     * @param string $key Key
     *
     * @return bool Item status
     */
    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     * Removes an item.
     *
     * @param string $key Key
     */
    public function __unset($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * Gets an item at the offset.
     *
     * @param string $offset Offset
     *
     * @return mixed Value
     */
    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : NULL;
    }

    /**
     * Sets an item at the offset.
     *
     * @param string $offset Offset
     * @param mixed  $value  Value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    /**
     * Checks if an item exists at the offset.
     *
     * @param string $offset Offset
     *
     * @return bool Item status
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Removes an item at the offset.
     *
     * @param string $offset Offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Resets the collection.
     */
    public function rewind()
    {
        reset($this->_data);
    }

    /**
     * Gets current collection item.
     *
     * @return mixed Value
     */
    public function current()
    {
        return current($this->_data);
    }

    /**
     * Gets current collection key.
     *
     * @return mixed Value
     */
    public function key()
    {
        return key($this->_data);
    }

    /**
     * Gets the next collection value.
     *
     * @return mixed Value
     */
    public function next()
    {
        return next($this->_data);
    }

    /**
     * Checks if the current collection key is valid.
     *
     * @return bool Key status
     */
    public function valid()
    {
        $key = key($this->_data);
        return ($key !== NULL && $key !== false);
    }

    /**
     * Gets the size of the collection.
     *
     * @return int Collection size
     */
    public function count()
    {
        return sizeof($this->_data);
    }

    /**
     * Gets the item keys.
     *
     * @return array Collection keys
     */
    public function keys()
    {
        return array_keys($this->_data);
    }

    /**
     * Gets the collection data.
     *
     * @return array Collection data
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Sets the collection data.
     *
     * @param array $data New collection data
     */
    public function setData(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Removes all items from the collection.
     */
    public function clear()
    {
        $this->_data = array();
    }

    public function getFirst()
    {
        if (!empty($this->_data)) {
            return $this->_data[0];
        }

        return NULL;
    }

    public function toOptionArray($key, $value)
    {
        $optionArray = array();
        foreach ($this->_data as $record) {
            $optionArray[$record->$key] = (is_array($value)) ? $this->_getArrayValueFromRecord($record, $value) : $record->$value;
        }

        return $optionArray;
    }

    private function _getArrayValueFromRecord($record, array $values)
    {
        $data = array();
        foreach ($values as $value) {
            $data[] = $record->$value;
        }
        return implode(' ', $data);
    }

    public function getColumnValues($column)
    {
        $values = array();
        foreach ($this->_data as $record) {
            if (!in_array($record[$column], $values)) {
                $values[] = $record[$column];
            }
        }

        return $values;
    }

    public function getRowsByColumnValue($column, $value)
    {
        $values = array();
        foreach ($this->_data as $record) {
            if (($record[$column] == $value)) {
                $values[] = $record;
            }
        }

        return new Collection($values);
    }

    public function countRowsByColumnValue($column, $value) {
        $total = 0;
        foreach ($this->_data as $record) {
            if (($record[$column] == $value)) {
                $total++;
            }
        }

        return $total;
    }

    public function delete()
    {
        foreach ($this->_data as $key => $row) {
            if (is_a($row, 'Model')) {
                $row->delete();
            }
            unset($this->_data[$key]);
        }
        return $this;
    }

    public function save()
    {
        foreach ($this->_data as $row) {
            if (is_a($row, 'Model')) {
                $row->save();
            }
        }
        return $this;
    }
}
