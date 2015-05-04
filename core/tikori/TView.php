<?php

class TView
{

    protected $_jsFiles = array();
    protected $_cssFiles = array();
    protected $_themes = array();
    protected $_usedTheme = 'default';

    public function __construct()
    {
        // check that there are any defined layout media files

        if ($files = Core::app()->cfg('layout/css')) {
            $this->_cssFiles = $files;
        }

        if ($files = Core::app()->cfg('layout/js')) {
            $this->_jsFiles = $files;
        }

        if ($themes = Core::app()->cfg('theme')) {
            if (!is_array($themes)) {
                $themes = array($themes);
            }

            foreach($themes as $theme) {
                if ($theme != 'default') {
                    $this->_themes[] = $theme;
                }
            }
        }
    }

    public function render($file = NULL, $data = NULL, $return = false)
    {
        //TODO: no error when file not found?

        try {
            if (!empty($file) && $this->viewExists($file)) {
                $out = $this->renderPartial($file, $data);
            } else {
                $out = (string)$data;
            }

            $out = $this->renderPartial($this->layout, array('content' => $out));
        } catch (Exception $e) {
            throw new Exception('Rendering view error: ' . $e->getMessage());
        }

        if ($return) {
            return $out;
        } else {
            echo $out;
        }
    }

    public function renderPartial($file, $data = NULL, $return = true)
    {
        if ($filename = $this->_findViewFile($file)) {
            return $this->renderInternal($filename, $data, $return);
        } else {
            throw new Exception('View ' . $file . ' not found.');
        }
    }

    public function renderInternal($_fileNC, $_dataNC = NULL, $_returnNC = false)
    {
        if (is_array($_dataNC)) {
            extract($_dataNC, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_dataNC;
        }

        if ($_returnNC) {
            ob_start();
            ob_implicit_flush(false);
            Profiler::addLog('Rendering <kbd>' . str_replace(Core::app()->appDir, '', $_fileNC) . '</kbd>');
            require($_fileNC);
            return ob_get_clean();
        } else {
            require $_fileNC;
        }
    }

    public function viewExists($view)
    {
        return ($this->_findViewFile($view) !== false);
    }

    protected function _findViewFile($file)
    {
        $paths = array();

        // TODO: cache this in variable at least
        if (substr($file, 0, 2) != '//') {

            if (isset($this->controller)) {
                if (!empty($this->_themes)) {
                    foreach($this->_themes as $theme) {
                        $paths[] = Core::app()->appDir . '/themes/' . $theme . '/' . $this->controller . '/';
                        $paths[] = Core::app()->coreDir . '/themes/' . $theme . '/' . $this->controller . '/';
                    }
                }
                $paths[] = Core::app()->appDir . '/views/' . $this->controller . '/';
                $paths[] = Core::app()->coreDir . '/views/' . $this->controller . '/';
            }

            $modules = Core::app()->cfg('modules');
            if (!empty($modules)) {
                //TODO: find a better way to get module name...
                $reflection = new ReflectionClass($this);
                $currentModule = strtolower(
                    preg_replace('#(?:.*?)modules(?:\\\|/)([a-zA-Z0-9_]*)(?:.*)#i', '$1', $reflection->getFilename())
                );

                if (!empty($currentModule)) {
                    foreach ($modules as $module => $config) {
                        $module = strtolower($module);
                        if ($module == $currentModule) {
                            $paths[] = Core::app()->appDir . '/modules/' . $module . '/views/';
                            /* strtolower($this->controller) . */
                            $paths[] = Core::app()->coreDir . '/modules/' . $module . '/views/';
                            /* strtolower($this->controller) . */
                        }
                    }
                }
            }
        }

        if (!empty($this->_themes)) {
            foreach ($this->_themes as $theme) {
                $paths[] = Core::app()->appDir . '/themes/' . $theme . '/';
                $paths[] = Core::app()->coreDir . '/themes/' . $theme . '/';
            }
        }
        $paths[] = Core::app()->appDir . '/views/';
        $paths[] = Core::app()->coreDir . '/views/';

        if (!empty($this->area)) {
            $addons = array();
            foreach ($paths as $entry) {
                $addons[] = $entry . $this->area . '/';
            }
            $paths = array_merge($addons, $paths);
        }

        $file = ltrim($file, '/');

        foreach ($paths as $path) {
            $filename = $path . $file . '.php';
            if (file_exists($filename)) {
                //TODO: change it so it won't use preg_match, to be much faster
                if (preg_match('/\/themes\/(.*?)\//i', $filename, $matches)) {
                    $this->_usedTheme = $matches[1];
                }
                return $filename;
            }
        }

        return false;
    }

    public function pageTitle() {
        return (!empty($this->pageTitle)) ? $this->pageTitle : Core::app()->cfg('appName');
    }

    public function getCssForHead($prefix = '')
    {
        $return = array();
        foreach ($this->_cssFiles as $cssSrc) {
            $return[] = '<link rel="stylesheet" href="' . $cssSrc . '">';
        }

        return implode(PHP_EOL . $prefix, $return);
    }

    public function getJsForHead($prefix = '') {
        $return = array();
        foreach ($this->_jsFiles as $jsSrc) {
            $return[] = '<script type="text/javascript" src="' . $jsSrc . '"></script>';
        }

        return implode(PHP_EOL . $prefix, $return);
    }
}
