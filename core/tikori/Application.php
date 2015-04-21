<?php

/**
 * Class Application
 */
abstract class Application
{

    const EVENT_BEFORE_DISPATCH = '_before_dispatch';
    const EVENT_AFTER_DISPATCH = '_after_dispatch';
    /**
     * @var Config
     */
    protected $_config = null;
    /**
     * @var string Application main directory (where index.php is)
     */
    public $appDir = '';
    public $coreDir = '';

    public $mode = null;
    public $autoloadPaths = array();
    /*
     * Array of autoload paths
     */
    public $namespaces = array('app', 'core');

    public function __construct($config = 'default')
    {
        // assign reference
        Core::setApplication($this);

        //TODO: enable
        //$this->registerCoreModules();
        //$this->configure();
        //$this->preloadModules();

        $this->init($config);
    }

    /**
     * It runs application. Whole magic is here, abracadabra!
     * Echoes results, throw exceptions and 404s, redirects etc.
     *
     * @param string $config config file name without .json, usually 'default'
     *
     * @throws Exception
     * @return bool
     */
    public function init($config = 'default')
    {
        // set directories
        $this->appDir = TIKORI_ROOT . DIRECTORY_SEPARATOR . 'app';
        $this->coreDir = TIKORI_FPATH;

        // register autoloads
        $this->registerAutoloadPaths();
        Profiler::addLog('Registered autoload');

        if (function_exists('xdebug_get_code_coverage')) {
            Profiler::addLog('XDEBUG IS ENABLED! It may slow down request');
        }

        // register error handlers
        if (!Core::isConsoleApplication()) {
            Error::registerErrors();
            ob_start('tikori');
            register_shutdown_function(array('Error', 'shutdown_handler'));
            Profiler::addLog('Registered errors');
        }

//		$this->defaultCfg();

        // config
        $this->reconfigure($config);

        $this->run($config);
    }

    public abstract function run($config);

    public function preloadModule($module, $config = NULL)
    {
//        foreach (array(true, false) as $includeCoreDir) {
//            $this->addAutoloadPaths(
//                array(
//                     'modules/' . $module,
//                     'modules/' . $module . '/config',
//                     'modules/' . $module . '/controllers',
//                     'modules/' . $module . '/models',
//                     'modules/' . $module . '/views',
//                     'modules/' . $module . '/widgets',
//                ), $includeCoreDir
//            );
//        }

        $this->registerAutoloadPaths($module);

        $moduleClass = ucfirst($module) . 'Module';
        if (Core::autoload($moduleClass, true)) {
            $class = new $moduleClass;
            $this->setComponent($module, $class);
        }
    }

    /**
     * Registers autoload paths for class searching
     */
    public function registerAutoloadPaths($module = '')
    {
        if (!empty($module)) {
            $module = 'modules/' . trim(strtolower($module), '/') . '/';
        }

        // core directory - can be shared on server :) false then true
        foreach (array(true, false) as $includeCoreDir) {
            $this->addAutoloadPaths(
                array(
                    $module . '',
                    #$module . 'config',
                    $module . 'common',
                    $module . 'controllers',
                    $module . 'models',
                    $module . 'helpers',
                    #$module . 'views',
                    $module . 'widgets',
                ), $includeCoreDir
            );
        }

        if (empty($module)) {
            $this->addAutoloadPaths(array('tikori'), true);
            $this->addAutoloadPaths(array('db'), true);
        }

//		var_dump($this->autoloadPaths);
//		die();
    }

    /**
     * Adds paths for autload if you need some another paths than default.
     * Usually it should be set in config and not called from app.
     *
     * @param string|array $paths Paths to add as array values
     *
     * @param bool $core Is it core path or not ?
     *
     * @return null
     */
    public function addAutoloadPaths($paths, $core = false)
    {
        if (is_string($paths)) {
            return $this->addAutoloadPaths(array($paths), $core);
        } else {
            foreach ($paths as $k => $dir) {
                foreach ($this->namespaces as $namespace) {
                    if ($namespace == 'core') {
                        $autodir = TIKORI_FPATH . '/' . trim($dir, '/');
                    } else {
                        $autodir = rtrim(TIKORI_ROOT . DIRECTORY_SEPARATOR . $namespace . '/' . trim($dir, '/'), '/');
                    }
                    if (!in_array($autodir, $this->autoloadPaths) /*and file_exists($autodir)*/) {
                        if (!empty($this->autoloadPaths)) {
                            array_unshift($this->autoloadPaths, $autodir);
                        } else {
                            $this->autoloadPaths[] = $autodir;
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets application mode
     *
     * @return int (Core::MODE_DEBUG, Core::MODE_DEV, Core::MODE_PROD)
     */
    public function getMode()
    {
        if ( /* $this->cfg('mode') */
            $this->mode === NULL
        ) {
            if (isset($_ENV['TIKORI_MODE'])) {
                $this->mode = $_ENV['TIKORI_MODE'];
            } else {
                $envMode = getenv('TIKORI_MODE');
                if ($envMode !== false) {
                    $this->mode = $envMode;
                } else {
                    $this->mode = ($this->cfg('mode') === NULL) ? Core::MODE_PROD : $this->cfg('mode');
                }
            }

            $this->mode = intval($envMode);

            switch ($this->mode) {
                case Core::MODE_DEBUG:
                case Core::MODE_DEV:
                    error_reporting(E_ALL | E_STRICT);
                    break;
//                case Core::MODE_PROD:
                default:
                    error_reporting(0);
//                default:
//                    header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
//                    echo 'You need to setup application mode, sorry.';
//                    exit(1);
            }
        }

        return $this->mode;
    }

    public function defaultCfg()
    {
        $this->_config = new CConfig(array(
            'appname' => 'Unknown application',
            //				'url' => DefC_Url::getDefValues(),
            //				'db' => DefC_Db::getDefValues(),
        ));
    }

    /**
     * Reconfigures application using json string or array
     *
     * @param array|string $config
     */
    public function reconfigure($config)
    {
        $this->cfg()->load($config);
        $this->getMode();
    }

    /**
     * @param string $item Path to item, like name or name/name
     * @param mixed $default Return this when nothing found
     *
     * @return TConfig|array
     */
    public function cfg($item = NULL, $default = NULL)
    {
        if ($this->_config === NULL) {
            $this->_config = new CConfig();
        }

        if ($item === NULL) {
            return $this->_config;
        } else {

            if (substr($item, strlen($item) - 2, 2) === '/*') {
                return $this->cfg(substr($item, 0, strlen($item) - 2));
            }
            // maybe later add code to search bt path/to/node/somet* ?

            return $this->_config->get($item, $default);
        }
    }

    public function flatcfg($item = NULL, $default = NULL)
    {
        if ($item === NULL) {
            return $this->_flatify($item, $this->_config);
        } else {
            return $this->_flatify($item, $this->_config->get($item, $default));
        }
    }

    protected function _flatify($item, $array)
    {
        $flat = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flat = array_merge($flat, $this->_flatify($item . '/' . $key, $value));
            } else {
                $flat[ltrim($item . '/' . $key, '/')] = $value;
            }
        }

        return $flat;
    }

    protected $_loadedModules = array();

    /**
     * Sets or unsets component
     *
     * @param string $id Identifier of component
     * @param TModule|null $module
     */
    public function setComponent($id, $module)
    {
        if ($module === NULL) {
            unset($this->_loadedModules[$id]);
        } else {
            $this->_loadedModules[$id] = $module;
            if (!$module->isInitialized()) {
                $module->init();
            }
        }
    }

    /**
     * @param array $components
     */
    public function setComponents(array $components)
    {
        foreach ($components as $id => $component) {
            $this->setComponent($id, $component);
        }
    }

    public function __get($value)
    {
        if (empty($this->_loadedModules[$value])) {
            return NULL;
        } else {
            return $this->_loadedModules[$value];
        }
    }

    public function component($componentName)
    {
        $module = $this->__get($componentName);
        if ($module === NULL) {
            return $this->setComponent(strtolower($componentName), new $componentName . 'Module');
        }
        return $this->__get($componentName);
    }

//	/**
//	 * Get / Set cfg value
//	 *
//	 * @param string $key
//	 * @param mixed $val
//	 * @return mixed Returns value for key or null if not found.
//	 */
//	public function cfg($key, $val = null) {
//		if ($val === null) {
//			if (array_key_exists($key, $this->_config)) {
//				return $this->_config[$key];
//			}
//			return null;
//		}
//		$this->_config[$key] = $val;
//		return $val;
//	}
}
