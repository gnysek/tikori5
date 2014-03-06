<?php

class TObject
{

    /**
     * @return string current class name
     */
    public static function className()
    {
        return get_called_class();
    }

    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new Exception('Getting unknown property ' . get_class($this) . '::' . $name);
        }
    }

    public function __set($name, $value)
    {
        $setter = 'set' . ucfirst($name);

        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } else {
            throw new Exception('Setting unknown property ' . get_class($this) . '::' . $name);
        }
    }

    public function __isset($name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return true;
        }
        return false;
    }

    public function __call($name, $params)
    {
        throw new Exception('Unknown method: ' . get_class($this) . '::' . $name . '()');
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}
