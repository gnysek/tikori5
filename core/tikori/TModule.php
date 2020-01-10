<?php

class TModule
{

    protected $__initialized = false;
    protected $__moduleName = '';

    public function init()
    {
        $this->__initialized = true;
        return true;
    }

    public function isInitialized()
    {
        return $this->__initialized;
    }

    public function addObserver($eventName, $what = null)
    {
        Core::app()->observer->addObserver($eventName, $what === null ? $this : $what);
    }

    public function updateConfig($path, $value)
    {
        if (Core::app()->dbconfig) {
            Core::app()->dbconfig->saveConfig($path, $value, $this->__moduleName);
        }
    }

    public function setModuleCfgName($name)
    {
        $this->__moduleName = $name;
    }
}
