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

    /**
     * @param $eventName
     * @param object|array|callable $observer
     * @param null $unique
     */
    public function addObserver($eventName, $observer, $unique = null)
    {
        if ($unique !== null) {
            $unique = '_' . $unique;
            if (!array_key_exists($unique, $this->_observers[$eventName])) {
                $this->_observers[$eventName][$unique] = $observer;
            }
        } else {
            $this->_observers[$eventName][] = $observer;
        }
    }

    public function fireEvent($eventName, $data = [])
    {

        if (!empty($this->_observers[$eventName])) {
            $methodName = '';
            $w = explode('_', strtolower($eventName));
            foreach ($w as $_w) {
                $methodName .= ucfirst(trim($_w));
            }
            $methodName = lcfirst($methodName) . self::EVENT_SUFFIX;

            Profiler::addLog(
                sprintf(
                    'Firing event <code>%s</code> <kbd>%s</kbd> with %s observer(s)',
                    $eventName, $methodName, count($this->_observers[$eventName])
                )
            );

            foreach ($this->_observers[$eventName] as $observer) {
                if (is_callable($observer)) {
                    ($observer)($data); // if function
                    Profiler::addLog('Fired event <code>' . $eventName . '</code> using passed closure.');
                } else if (is_array($observer) and count($observer) == 2 and method_exists($observer[0], $observer[1])) {
                    call_user_func_array(array($observer[0], $observer[1]), array($data)); // if array pointing to function
                    Profiler::addLog('Fired event <code>' . $eventName . '</code> using <code>' . get_class($observer[0]) . '::' . $observer[1] . '</code>');
                } else if (!is_array($observer) and method_exists($observer, $methodName)) {
                    call_user_func_array(array($observer, $methodName), array($data)); // if just an object, so method name is generated automatically
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
