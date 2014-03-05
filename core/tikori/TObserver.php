<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 24.01.13
 * Time: 15:07
 * To change this template use File | Settings | File Templates.
 */
class TObserver
{

    const EVENT_SUFFIX = 'Event';

    protected $_observers = array();

    public function addObserver($eventName, $observer)
    {
        if (!array_key_exists($eventName, $this->_observers) or !array_key_exists(
            get_class($observer), $this->_observers[$eventName]
        )
        ) {
            $this->_observers[$eventName][get_class($observer)] = $observer;
        }
    }

    public function fireEvent($eventName, $data)
    {

        if (!empty($this->_observers[$eventName])) {
            $methodName = '';
            $w = explode('_', strtolower($eventName));
            foreach ($w as $_w) {
                $methodName .= ucfirst(trim($_w));
            }
            $methodName = lcfirst($methodName) . self::EVENT_SUFFIX;

            Profiler::addLog(
                'Firing event <code>' . $eventName . '</code> <kbd>' . $methodName . '</kbd> with ' . count(
                    $this->_observers[$eventName]
                ) . ' observer(s)'
            );
            foreach ($this->_observers[$eventName] as $observer) {
                if (method_exists($observer, $methodName)) {
                    call_user_func_array(array($observer, $methodName), array($data));
                }
            }
        } else {
            Profiler::addLog('Firing event <code>' . $eventName . '</code> but there\'s no observers');
        }
    }
}
