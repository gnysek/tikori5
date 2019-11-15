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
     * @var array|TModel[]
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
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Set an item.
     *
     * @param string $key Key
     * @param mixed $value Value
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
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }

    /**
     * Sets an item at the offset.
     *
     * @param string $offset Offset
     * @param mixed $value Value
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
        return ($key !== null && $key !== false);
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
     * Reverses data
     *
     * @return $this
     */
    public function reverse()
    {
        $this->_data = array_reverse($this->_data);
        return $this;
    }

    /**
     * @param $data
     */
    public function push($data)
    {
        array_push($this->_data, $data);
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

    /**
     * @return mixed|null|TModel|$this|$this[]
     */
    public function getFirst()
    {
        if (!empty($this->_data)) {
            return $this->_data[0];
        }

        return null;
    }

    public function toOptionArray($key, $value)
    {
        $optionArray = array();
        foreach ($this->_data as $record) {
            $optionArray[$record->$key] = (is_array($value)) ? $this->_getArrayValueFromRecord($record, $value) : $record->$value;
        }

        return $optionArray;
    }

    public function toArray()
    {
        return $this->_data;
    }

    private function _getArrayValueFromRecord($record, array $values)
    {
        $data = array();
        foreach ($values as $value) {
            $data[] = $record->$value;
        }
        return implode(' ', $data);
    }

    /**
     * @param $column
     *
     * @return array
     */
    public function getColumnValues($column)
    {
        $values = array();

        $isSubproperty = strpos($column, '.');

        if ($isSubproperty > 0) {

            // todo: what in case, where there's more nests like a.b.c.d?

            $subRelation = substr($column, 0, $isSubproperty);
            $subProperty = substr($column, $isSubproperty + 1);

            foreach ($this->_data as $record) {
                if (is_array($record->$subRelation)) {
                    foreach ($record->$subRelation as $subRecord) {
                        if (!in_array($subRecord[$subProperty], $values)) {
                            $values[] = $subRecord[$subProperty];
                        }
                    }
                } else {
                    $values[] = $record->{$subRelation}[$subProperty];
                }
            }
        } else {
            foreach ($this->_data as $record) {
                if (!in_array($record[$column], $values)) {
                    $values[] = $record[$column];
                }
            }
        }

        return $values;
    }

    /**
     * @param $column
     * @param $value
     * @return Collection|TModel[]|$this[]|self[]
     * @throws Exception
     */
    public function getRowsByColumnValue($column, $value)
    {
        $values = array();
        foreach ($this->_data as $record) {
            if ($record[$column] == $value) {
                $values[] = $record;
            }
        }

        return new Collection($values);
    }

    /**
     * @param $column
     * @param array $values
     * @return Collection|TModel[]|$this[]|self[]
     */
    public function getRowsWhereColumnValues($column, array $values = [])
    {
        $result = array();
        foreach ($this->_data as $record) {
            if (in_array($record[$column], $values)) {
                $result[] = $record;
            }
        }

        return new Collection($result);
    }

    public function getFirstRowByColumnValue($column, $value)
    {
        $values = array();
        foreach ($this->_data as $record) {
            if (($record[$column] == $value)) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Gets value for field $relationField from related record $relationName, by this collection $column and $value
     * like for item with category_id == 8: $model->category->name == $this->getRelatedValueByColumnValue('category_id', 8, 'category', 'name');
     * @param $column
     * @param $value
     * @param $relationName
     * @param $relationField
     * @return Collection|mixed|null
     */
    public function getRelatedValueByColumnValue($column, $value, $relationName, $relationField)
    {
        foreach ($this->_data as $record) {
            if (($record[$column] == $value)) {
                return $record->$relationName->$relationField;
            }
        }

        return null;
    }

    public function getWhereNotEmpty($column)
    {
        $values = array();
        foreach ($this->_data as $record) {
            if ($value = $record->$column) {
                if ($value instanceof Collection) {
                    if ($value->count() > 0) {
                        $values[] = $record;
                    }
                } else {
                    $values[] = $record;
                }
            }
        }

        return new Collection($values);
    }

    /**
     * @param $function
     * @return Collection
     */
    public function getWhere($function, $additionalParams = array())
    {
        $values = array();

        foreach ($this->_data as $record) {

            if (count($additionalParams)) {
                $result = call_user_func_array($function, array($record) + $additionalParams);
                if ($result == true) {
                    $values[] = $record;
                }
            } else {
                if ($function($record) == true) {
                    $values[] = $record;
                }
            }
        }

        return new Collection($values);
    }

    /**
     * @return Collection
     */
    public function getRandomized()
    {
        $values = $this->_data;
        shuffle($values);
        return new Collection($values);
    }

    public function countRowsByColumnValue($column, $value)
    {
        $total = 0;
        foreach ($this->_data as $record) {
            if (($record[$column] == $value)) {
                $total++;
            }
        }

        return $total;
    }

    public function countRowsWhereNotEmpty($column)
    {
        $total = 0;
        foreach ($this->_data as $record) {
            if (!empty($record[$column])) {
                $total++;
            }
        }

        return $total;
    }

    public function inCollection($column, $value)
    {
        foreach ($this->_data as $record) {
            if (isset($record[$column]) and $record[$column] == $value) {
                return true;
            }
        }

        return false;
    }

    public function delete()
    {
        foreach ($this->_data as $key => $row) {
            // TODO mass deletion
            if (is_a($row, 'TModel')) {
                $row->delete();
            }
            unset($this->_data[$key]);
        }
        return $this;
    }

    public function save($forceToSave = false)
    {
        foreach ($this->_data as $row) {
            if (is_a($row, 'TModel')) {
                $row->save($forceToSave);
            }
        }
        return $this;
    }
}
