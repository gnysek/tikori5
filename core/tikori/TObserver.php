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
                if (is_callable($observer)) {
                    ($observer)($data);
                    Profiler::addLog('Fired event <code>' . $eventName . '</code> using passed closure.');
                } else if (is_array($observer) and count($observer) == 2 and method_exists($observer[0], $observer[1])) {
                    call_user_func_array(array($observer[0], $observer[1]), array($data));
                    Profiler::addLog('Fired event <code>' . $eventName . '</code> using <code>' . get_class($observer[0]) . '::' . $observer[1] . '</code>');
                } else if (!is_array($observer) and method_exists($observer, $methodName)) {
                    call_user_func_array(array($observer, $methodName), array($data));
                    Profiler::addLog('Fired event <code>' . $eventName . '</code> using <code>' . get_class($observer) . '::' . $methodName . '</code>');
                } else {
                    Profiler::addLog('Firing event <code>' . $eventName . '</code> which is registered but there\'s no method <code>' . $methodName . '</code> for it.');
                }
            }
        } else {
            Profiler::addLog('Firing event <code>' . $eventName . '</code> but there\'s no observers.');
        }
    }
}
