<?php

class TView
{

    protected $_themes = array();
    protected $_usedTheme = 'default';
    public $context = null;
    public $area = null;

    const EVENT_THEMES_REGISTER = 'tview_themes_register';
    const EVENT_VIEW_FILE_FOUND = 'tview_view_file_found';
    const EVENT_VIEW_FILE_AFTER_RENDER = 'tview_view_file_after_render';

    public function __construct()
    {
        // check that there are any defined layout media files

        if ($files = Core::app()->cfg('layout/css')) {
            foreach($files as $css) {
                Asset::requireCSS($css);
            }
        }

        if ($files = Core::app()->cfg('layout/js')) {
            foreach($files as $js) {
                Asset::requireJS($js);
            }
        }

        if ($themes = Core::app()->cfg('theme')) {
            if (!is_array($themes)) {
                $themes = array($themes);
            }

            Core::app()->observer->fireEvent(self::EVENT_THEMES_REGISTER, ['themes' => &$themes]);

            foreach($themes as $theme) {
                if ($theme != 'default') {
                    $this->_themes[] = $theme;
                }
            }
        }
    }

    /**
     * @param null $file
     * @param null $data
     * @param bool $return
     * @return string
     * @throws Exception
     */
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
            throw new Exception('Rendering view <code>' . $file . '</code> error: ' . $e->getMessage() . ', the context was <code>' . get_class($this) . '</code>', $e->getCode(), $e);
        }

        Core::app()->observer->fireEvent('render_finished', array('output' => &$out));

        if ($return) {
            return $out;
        } else {
            echo $out;
        }
    }

    /**
     * @param $file
     * @param null $data
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function renderPartial($file, $data = NULL, $return = true)
    {
        $bench = Profiler::benchStart('TView', $file);
        $result = $this->renderPartialInContext($file, $this->context, $data, $return);
        Profiler::benchFinish($bench);
        return $result;
    }

    /**
     * @param $file
     * @param null $data
     * @param bool $return
     * @throws Exception
     */
    public function renderIfAjax($file, $data = NULL, $return = false) {
        if (Core::app()->request->isAjax()) {
            $this->renderPartial($file, $data , $return);
        } else {
            $this->render($file, $data, $return);
        }
    }

    /**
     * @param $file
     * @param null $context
     * @param null $data
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function renderPartialInContext($file, $context = null, $data = null, $return = true)
    {
        if ($filename = $this->_findViewFile($file, $context)) {
            return $this->renderInternal($filename, $data, $return);
        } else {
            throw new Exception('View <kbd>' . $file . '</kbd> not found.');
        }
    }

    /**
     * @param $_fileNC
     * @param null $_dataNC
     * @param bool $_returnNC
     * @return false|string
     */
    public function renderInternal($_fileNC, $_dataNC = NULL, $_returnNC = false)
    {
        if (is_array($_dataNC)) {
            extract($_dataNC, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_dataNC; // this will be used directly in rendered file
        }

        ob_start();
        ob_implicit_flush(false);
        Profiler::addLog('Rendering <kbd>' . str_replace(Core::app()->appDir, '', $_fileNC) . '</kbd>');
        if (Core::app()->getMode() != Core::MODE_PROD) {
            echo '<!-- START ' . str_replace(Core::app()->appDir, '', $_fileNC) . ' -->' . PHP_EOL;
        }
        require $_fileNC;
        if (Core::app()->getMode() != Core::MODE_PROD) {
            echo '<!-- END ' . str_replace(Core::app()->appDir, '', $_fileNC) . ' -->' . PHP_EOL;
        }
        $result = ob_get_clean();

        Core::app()->observer->fireEvent(self::EVENT_VIEW_FILE_AFTER_RENDER, ['result' => &$result, 'themes' => $this->_themes]);

        if ($_returnNC) {
            return $result;
        } else {
            echo $result;
        }
    }

    /**
     * @param string $view
     * @param null|object $context
     * @return bool
     * @throws ReflectionException
     */
    public function viewExists($view, $context = null)
    {
        return ($this->_findViewFile($view, $context) !== false);
    }

    protected static $_viewFiles = null;
    protected static $_viewFilesChanged = false;
    const TEMPLATE_CACHE = '__TEMPLATES__';

    /**
     * @param string $file
     * @param null|object $context
     * @return bool|string
     * @throws ReflectionException
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

        if (!is_array(self::$_viewFiles)) {
            // in case of json_decode fail
            self::$_viewFiles = array();
        }

        // if (!is_array(self::$_viewFiles)) {
        //     var_dump(self::$_viewFiles);
        //     die();
        // }

        $currentThemeIdentifier = (count($this->_themes) ? 'T:' . ($this->_themes[0] . '|') : '') . 'A:' . $this->area . '|F:';

        if (array_key_exists($currentThemeIdentifier . $file, self::$_viewFiles)) {
            Profiler::addLog(sprintf('Loaded template from cache: <kbd>%s</kbd> <kbd>[%s]</kbd>', self::$_viewFiles[$currentThemeIdentifier . $file], $currentThemeIdentifier . $file));
            $_return = self::$_viewFiles[$currentThemeIdentifier . $file];
            Core::app()->observer->fireEvent(self::EVENT_VIEW_FILE_FOUND, ['file' => &$_return, 'themes' => $this->_themes]);
            return $_return;
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

                if ($context == null and $this->context !== null) {
                    $context = $this->context;
                }

                $reflection = new ReflectionClass(($context !== null && is_object($context)) ? $context : $this);
                $currentModule = null;
                if (preg_match('/(?:\/|\\\)modules(?:\/|\\\)/', $reflection->getFileName())) {
                    $currentModule = strtolower(
                        preg_replace('#(?:.*?)modules(?:\\\|/)([a-zA-Z0-9_]*)(?:.*)#i', '$1', $reflection->getFilename())
                    );
                }

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

        if (!empty($this->area) and substr($file, 0, 1) != '/') {
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

                if (!in_array($currentThemeIdentifier . $file, self::$_viewFiles)) {
                    self::$_viewFiles[$currentThemeIdentifier . $file] = str_replace('\\', '/', $filename);
                    self::$_viewFilesChanged = true;
                    Profiler::addLog(sprintf('Loaded template + added to cache: <kbd>%s</kbd> <kbd>[%s]</kbd>', $filename, $currentThemeIdentifier . $file));
                } else {
                    Profiler::addLog(sprintf('Displayed template: <kbd>%s</kbd> <kbd>[%s]</kbd>', $filename, $currentThemeIdentifier . $file));
                }

                $_return = $filename;
                Core::app()->observer->fireEvent(self::EVENT_VIEW_FILE_FOUND, ['file' => &$_return, 'themes' => $this->_themes]);
                return $_return;
            }
        }

        return false;
    }

    public function __destruct()
    {
        if (self::$_viewFilesChanged and is_array(self::$_viewFiles)) {
            ksort(self::$_viewFiles);
            Core::app()->cache->saveCache(self::TEMPLATE_CACHE, json_encode(self::$_viewFiles, JSON_PRETTY_PRINT));
            self::$_viewFilesChanged = false;
        }
    }

    public function pageTitle() {
        return (!empty($this->pageTitle)) ? $this->pageTitle : Core::app()->cfg('appName');
    }

    public function __()
    {
        return call_user_func_array('__', func_get_args());
    }

    public function hasTheme($theme)
    {
        return in_array($theme, $this->_themes);
    }
}
