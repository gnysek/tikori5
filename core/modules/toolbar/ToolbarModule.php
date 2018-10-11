<?php

class ToolbarModule extends TModule
{

    public function init()
    {
        $this->addObserver('render_finished');
        return parent::init();
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function renderFinishedEvent($data)
    {
        $view = new TView();

        $output = $view->renderPartialInContext('toolbar', $this, array('tabs' => $this->_tabs, 'nf' => $this->_notificationNum, 'values' => $this->_tabValues, 'status' => implode(' | ', $this->_status)));

        if (stripos($data['output'], '</body>') > 1) {
            $data['output'] = str_replace('</body>', $output . '</body>', $data['output']);
        } else {
            $data['output'] .= $output;
        }
    }

    protected $_tabs = array();
    protected $_tabValues = array();
    protected $_status = array();
    protected $_notificationNum = array();

    public function putValueToTab($tab, $value)
    {
        if (!in_array($tab, $this->_tabs)) {
            $this->_tabs[] = $tab;
        }

        $this->_tabValues[$tab][] = $value;
    }

    public function setNotificationsNumberOnTab($tab, $value)
    {
        $this->_notificationNum[$tab] = $value;
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
}
