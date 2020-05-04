<?php

class ToolbarModule extends TModule
{

    protected $_tabs = array();
    protected $_tabValues = array();
    protected $_status = array();
    protected $_notificationNum = array();
    protected $_counters = array();
    protected $_timers = array();

    public function init()
    {
        $this->addObserver('render_finished');
        return parent::init();
    }

    /**
     * Called on 'render_finished' event
     * @param $data
     * @throws Exception
     */
    public function renderFinishedEvent($data)
    {
        if (Core::app()->mode != Core::MODE_PROD) {
            $view = new TView();

            if (!TProfiler::$getLogsGetAtLeastOnce) {
                Profiler::getLogs(); // to get status
            }

            $output = $view->renderPartialInContext('toolbar', $this, array(
                'tabs'     => $this->_tabs,
                'nf'       => $this->_notificationNum,
                'values'   => $this->_tabValues,
                'status'   => implode(' | ', $this->_status),
                'counters' => $this->_counters,
                'timers'   => $this->_timers,
                'timeline' => Profiler::getBenchForToolbar(),
            ));

            if (stripos($data['output'], '</body>') > 1) {
                $data['output'] = str_replace('</body>', $output . '</body>', $data['output']);
            } else {
                $data['output'] .= $output;
            }
        }
    }

    public function putValueToTab($tab, $value, $notif = null)
    {
        if (!in_array($tab, $this->_tabs)) {
            $this->_tabs[] = $tab;
        }

        $this->_tabValues[$tab][] = $value;

        if ($notif !== null) {
            $this->setNotificationsNumberOnTab($tab, $notif);
        }
    }

    public function setNotificationsNumberOnTab($tab, $value)
    {
        $this->_notificationNum[$tab] = $value;
    }

    public function getNotificationsNumberOnTab($tab)
    {
        return array_key_exists($tab, $this->_notificationNum) ? $this->_notificationNum[$tab] : 0;
    }

    public function addStatus($text)
    {
        $this->_status[] = $text;
    }

    public static function debug($val)
    {
        if (Core::app()->hasLoadedModule('toolbar')) {
            ob_start();
            var_dump($val);
            $result = ob_get_clean();
            Core::app()->toolbar->putValueToTab('debug', $result . '<br>');
            //Core::app()->toolbar->putValueToTab('debug', var_export($val, true));
        }
    }

    /**
     * @param $counterName
     * @param int $value
     * @static
     */
    public function addCounter($counterName, $value = 1)
    {
        if (isset($this) && $this instanceof self) {
            if (!array_key_exists($counterName, $this->_counters)) {
                $this->_counters[$counterName] = 0;
            }
            $this->_counters[$counterName] += $value;
        } else {
            if (Core::app()->hasLoadedModule('toolbar')) {
                Core::app()->toolbar->addCounter($counterName, $value);
            }
        }
    }

    /**
     * @param $timerName
     * @param int $value
     * @static
     */
    public function addTimer($timerName, $value = 1)
    {
        if (isset($this) && $this instanceof self) {
            if (!array_key_exists($timerName, $this->_timers)) {
                $this->_timers[$timerName] = 0;
            }
            $this->_timers[$timerName] += $value;
        } else {
            if (Core::app()->hasLoadedModule('toolbar')) {
                Core::app()->toolbar->addCounter($timerName, $value);
            }
        }
    }
}
