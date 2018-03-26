<?php

class TView
{

    protected $_jsFiles = array();
    protected $_cssFiles = array();
    protected $_themes = array();
    protected $_usedTheme = 'default';
    public $area = null;

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
            if (!empty($file)) {
                $out = $this->renderPartial($file, $data);
            } else {
                $out = (string)$data;
            }

            $out = $this->renderPartial($this->layout, array('content' => $out));
        } catch (Exception $e) {
            throw new Exception('Rendering view <code>' . $file . '</code> error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        Core::app()->observer->fireEvent('render_finished', array('output' => &$out));

        if ($return) {
            return $out;
        } else {
            echo $out;
        }
    }

    public function renderPartial($file, $data = NULL, $return = true)
    {
        return $this->renderPartialInContext($file, null, $data, $return);
    }

    public function renderIfAjax($file, $data = NULL, $return = false) {
        if (Core::app()->request->isAjax()) {
            $this->renderPartial($file, $data , $return);
        } else {
            $this->render($file, $data, $return);
        }
    }

    public function renderPartialInContext($file, $context = null, $data = null, $return = true)
    {
        if ($filename = $this->_findViewFile($file, $context)) {
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
            if (Core::app()->getMode() != Core::MODE_PROD) {
                echo '<!-- START ' . str_replace(Core::app()->appDir, '', $_fileNC) . ' -->';
            }
            require($_fileNC);
            if (Core::app()->getMode() != Core::MODE_PROD) {
                echo '<!-- END ' . str_replace(Core::app()->appDir, '', $_fileNC) . ' -->';
            }
            return ob_get_clean();
        } else {
            require $_fileNC;
        }
    }

    /**
     * @param string      $view
     * @param null|object $context
     * @return bool
     */
    public function viewExists($view, $context = null)
    {
        return ($this->_findViewFile($view, $context) !== false);
    }

    protected static $_viewFiles = null;
    protected static $_viewFilesChanged = false;
    const TEMPLATE_CACHE = '__TEMPLATES__';

    /**
     * @param string      $file
     * @param null|object $context
     * @return bool|string
     */
    protected function _findViewFile($file, $context = null)
    {
        if (self::$_viewFiles == null) {
            self::$_viewFiles = array();
            if (Core::app()->cache && Core::app()->cache->findCache(self::TEMPLATE_CACHE) && (Core::app()->request && !Core::app()->request->isHardRefresh())) {
                Profiler::addLog('Loaded templates cache');
                self::$_viewFiles = json_decode(Core::app()->cache->loadCache(self::TEMPLATE_CACHE), true);
            }
        }

        // if (!is_array(self::$_viewFiles)) {
        //     var_dump(self::$_viewFiles);
        //     die();
        // }

        $cacheList = array('');

        if (isset($this->controller) and !empty($this->_themes)) {
            $cacheList = $this->_themes + array('');
        }

        foreach ($cacheList as $theme) {
            if (array_key_exists($this->area . $theme . $file, self::$_viewFiles)) {
                return self::$_viewFiles[$this->area . $theme . $file];
            }
        }

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
                $reflection = new ReflectionClass(($context !== null && is_object($context)) ? $context : $this);
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

                if (!in_array($file, self::$_viewFiles)) {
                    self::$_viewFiles[$this->area . ($this->_usedTheme != 'default' ? $this->_usedTheme : '') . $file] = str_replace('\\', '/', $filename);
                    self::$_viewFilesChanged = true;
                }

                return $filename;
            }
        }

        return false;
    }

    public function __destruct()
    {
        if (self::$_viewFilesChanged) {
            ksort(self::$_viewFiles);
            Core::app()->cache->saveCache(self::TEMPLATE_CACHE, json_encode(self::$_viewFiles));
            self::$_viewFilesChanged = false;
        }
    }

    public function pageTitle() {
        return (!empty($this->pageTitle)) ? $this->pageTitle : Core::app()->cfg('appName');
    }

    public function getCssForHead($prefix = '')
    {
        $return = array();

        if (Core::app()->cfg('layout/cssmerge', false) == true) {
            $return = Asset::mergeCssAssets($this->_cssFiles);
        } else {
            foreach ($this->_cssFiles as $cssSrc) {
                $return[] = Asset::cssAsset($cssSrc);//'<link rel="stylesheet" href="' . $cssSrc . '">';
            }
        }

        return implode(PHP_EOL . $prefix, $return);
    }

    public function getJsForHead($prefix = '')
    {
        $return = array();
        foreach ($this->_jsFiles as $jsSrc) {
            $return[] = '<script type="text/javascript" src="' . $jsSrc . '"></script>';
        }

        return implode(PHP_EOL . $prefix, $return);
    }

    public function __()
    {
        return call_user_func_array('__', func_get_args());
    }
}
