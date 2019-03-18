<?php

class TWidget extends TView
{

    public static $widgetStack = array();

    public static function begin($properties = array())
    {
        $class = get_called_class();
        /* @var $widget TWidget|mixed */
        $widget = new $class();
        $widget->setupProperties($properties);
        $widget->onCreate();

        self::$widgetStack[] = $widget;

        return $widget;
    }

    public static function end()
    {
        if (!empty(self::$widgetStack)) {
            $widget = array_pop(self::$widgetStack);
            if (get_class($widget) === get_called_class()) {
                echo $widget->run();
                return $widget;
            } else {
                throw new Exception("Expecting end() of " . get_class($widget) . ", found " . get_called_class());
            }
        } else {
            throw new Exception("Unexpected " . get_called_class() . '::end() call. A matching begin() for this widget is not found.');
        }
    }


    public function onCall()
    {
        return null;
    }

    public function onCreate()
    {

    }

    public function setupProperties($properties)
    {
        $cfg = Core::app()->cfg('widgets/' . str_replace('widget', '', strtolower(get_called_class())), array());

        if (is_array($cfg) and count($cfg) > 0) {
            $properties = array_merge($properties, $cfg);
        }

        foreach ($properties as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }

        return $this;
    }

    private $_data = array();

    public function __get($key)
    {
        return (array_key_exists($key, $this->_data)) ? $this->_data[$key] : null;
    }

    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }
}
