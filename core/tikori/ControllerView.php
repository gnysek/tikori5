<?php

class ControllerView extends TView
{

    protected $_widgets = array();

    /**
     * @param string $class
     * @param array  $properties
     * @param bool   $captureOutput
     * @return Widget
     */
    public function widget($class, $properties = array(), $captureOutput = false)
    {
        if ($captureOutput) {
            ob_start();
            ob_implicit_flush(false);
        }

        $widget = $this->_createWidget($class, $properties);
        $widget->run();

        if ($captureOutput) {
            return ob_get_clean();
        }

        return $widget;
    }

    private function _createWidget($class, $properties)
    {
        if (!array_key_exists($class, $this->_widgets)) {
            $className = ucfirst($class) . 'Widget';
            $widget = new $className;
            $widget->setupProperties($properties);
            $widget->init();
            $this->_widgets[$class] = $widget;
        }
        return $this->_widgets[$class];
    }
}
