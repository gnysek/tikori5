<?php

class Record implements IteratorAggregate, ArrayAccess, Countable {

	/**
	 * Keeps properties of this record
	 * @var array 
	 */
	private $_data = array();

	/**
	 * Is this record locked for new fields or not.
	 * Should be locked after record fields are loaded from DB and set
	 * @var boolean
	 */
	private $_locked = false;

	/**
	 * Sets record data to $array, and will $lock record for new fields by default
	 * @param array $array
	 * @param boolean $lock
	 * @return boolean|\Record
	 */
	public function import($array = array(), $lock = true) {
		if ($this->_locked) {
			return false;
		}

		foreach ($array as $k => $v) {
			$this->_data[$k] = $v;
		}

		if ($lock) {
			$this->_locked = true;
		}
		return $this;
	}

	public function getProperties() {
		return $this->data;
	}

	public function getFields() {
		return array_keys($this->_data);
	}

	public function getID() {
		return 0;
	}

	public function __toString() {
		return json_encode($this->_data);
	}

	public function isEmpty() {
		return (empty($this->_data));
	}

	/**
	 * Gets record data by field $offset
	 * @param mixed $offset
	 * @return mixed
	 */
	public function __get($offset) {
		if (!$this->offsetExists($offset)) {
			return null;
		}

		return $this->_data[$offset];
	}

	/**
	 * Gets record data by field $offset
	 * If record is locked, then will set only if $offset exists
	 * @param mixed $offset
	 * @param mixed $value
	 * @return boolean
	 */
	public function __set($offset, $value) {
		if ($this->_locked) {
			if (!$this->offsetExists($offset)) {
				return false;
			}
		}

		$this->_data[$offset] = $value;
		return true;
	}

	public function offsetExists($offset) {
		return (array_key_exists($offset, $this->_data));
	}

	public function offsetGet($offset) {
		$this->__get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset) {
		if ($this->_locked) {
			$this->__set($offset, null);
		}
		unset($this->_data[$offset]);
	}

	public function count() {
		return count($this->_data);
	}

	public function getIterator() {
		return new ArrayIterator($this->_data);
	}

}
