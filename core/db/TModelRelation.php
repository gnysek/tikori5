<?php

class TModelRelation implements ArrayAccess
{

    public $ownerClass;
    public $relationName;
    public $relationType;
    public $class;
    public $byField;

    public function __construct($relName, $relType, $ownerClass, $destClass, $byField)
    {
        $this->relationName = $relName;
        $this->relationType = $relType;
        $this->ownerClass = $ownerClass;
        $this->class = $destClass;
        $this->byField = $byField;
    }

    /**
     * Whether a offset exists
     * @param mixed $offset
     * @return boolean
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return ($offset >= 0 and $offset <= 4);
    }

    /**
     * Offset to retrieve
     * @param mixed $offset
     * @return mixed
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 0:
                return $this->relationType;
                break;
            default:
                return null;
        }
    }

    /**
     * Offset to set
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @since 5.0.0
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception('Sorry, but relations cannot be changed this way!');
    }

    /**
     * Offset to unset
     * @param mixed $offset
     * @return void
     * @since 5.0.0
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Sorry, but relations cannot be changed this way!');
    }

}
