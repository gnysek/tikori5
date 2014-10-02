<?php

/**
 * @property string                        $appDir         Application main directory (where index.php is)
 * @property Request                       $request
 * @property Response                      $response
 * @property Route                         $route
 * @property int                           $mode           Core::MODE_XXX
 * @property DbAbstract                    $db
 * @property Cache                         $cache          Cache module
 * @property SessionModule|Session         $session        Cache module
 * @property array                         $autoloadPaths  Array of autoload paths
 * @property Observer                      observer
 */
class Tikori
{

    const EVENT_BEFORE_DISPATCH = '_before_dispatch';
    const EVENT_AFTER_DISPATCH = '_after_dispatch';
    /**
     * @var Config
     */
    private $_config = NULL;
    public $appDir = '';
    public $coreDir = '';

    /**
     * @var Request
     */
    public $request = NULL;

    /**
     * @var Response
     */
    public $response = NULL;

    /**
     * @var Lang
     */
    public $lang = NULL;
    public $mode = NULL;
    public $autoloadPaths = array();
    public $namespaces = array('app', 'core');

    /**
     * @var Route
     */
    public $route = NULL;

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

    public function registerCoreModules()
    {
        $modules = array(
            'errorHandler'  => array('class' => 'Error'),
            'session'       => array('class' => 'TSession'),
            'user'          => array('class' => 'TUser'),
            'cache'         => array('class' => 'TCache'),
            'widgetFactory' => array('class' => 'TWidgetFactory'),
        );
        //TODO: enable
//        $this->setModules($modules);
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

        if (function_exists('xdebug_get_code_coverage')) {
            Profiler::addLog('XDEBUG IS ENABLED! It may slow down request');
        }

        Profiler::addLog('Registered autoload');

        // register error handlers
        Error::registerErrors();
        ob_start('tikori');
        register_shutdown_function(array('Core', 'shutdown_handler'));
        Profiler::addLog('Registered errors');

        if (ini_get('short_open_tag') != 1) {
            throw new Exception('Tikori 5 requires short_open_tag enabled!');
        }

//		$this->defaultCfg();

        // config
        $this->reconfigure($config);

        if (file_exists('.maintenance')) {
            // load languages
            $this->request = new Request();

            $this->lang = new Lang();
            $this->lang->loadLanguages();

            $this->response = new Response();
            $this->response->status(503);
            $view = new TView();
            $view->renderPartial('error.503', array(), false);
            $this->response->send();
            exit;
        }

        // enable cache, we need that for config
        $this->setComponent('cache', new Cache());
        $regenerateAutloads = false;
        if ($this->cache->findCache('config-sum')) {

        }

        // route
        Route::reconfigure();

        $this->observer = new Observer();

        // configure modules
        $modules = $this->cfg('modules');
        if (!empty($modules)) {
            foreach ($this->cfg('modules') as $module => $config) {
                $this->preloadModule($module, $config);
            }
        }

        if ($this->cfg('db/type') != "") {
            if ($this->cfg('db/type') == 'mysqli') {
                $db = new DbMySqli();
            } else {
                $db = new DbPDO();
            }
            $this->setComponent('db', $db);
        }

        // default routes
//		Route::set('tikori-admin', '<directory>(/<controller>(/<action>(/<id>)))(.html)', array('directory' => 'admin', 'id' => '.+'))
//			->defaults(array(
//				'controller' => 'admin',
//				'action' => 'index',
//			));
////		Route::set('tikori-default', '(<controller>(/<action>(/<id>)))(.html)')
        Route::set('tikori-default', '(<controller>(/<action>(/<tparams>)))', array('tparams' => '[a-zA-Z0-9_/]+'))
            ->defaults(
                array(
                     'controller' => ($this->cfg('default') !== NULL) ? $this->cfg('default') : 'default',
                     'action'     => 'index',
                )
            );

        // request
        $this->request = new Request();
        Profiler::addLog('Request created');
        $this->response = new Response();
        Profiler::addLog('Response created');

        // load languages
        $this->lang = new Lang();
        $this->lang->loadLanguages();

        // process route
        $this->route = Route::process_uri($this->request->getRouterPath());

        Profiler::addLog('Route processed and is ' . (($this->route == NULL) ? 'not found' : 'found'));

        Core::event(self::EVENT_BEFORE_DISPATCH);

        $this->_runController($this->route);

        Core::event(self::EVENT_AFTER_DISPATCH);

        Profiler::addLog('Route handled');

        $this->response->send();

        Profiler::addLog('Finishing application');
        if ($this->mode != Core::MODE_PROD) {
            echo Profiler::getLogs();
        }
        return true;
    }

    private function _runController($route)
    {
        if (($ca = $this->_createController($route)) !== NULL) {
            list($controller, $action) = $ca;
            /* @var $route Route */
            /* @var $controller Controller */

//            if ($route !== null) {

            Profiler::addLog(
                'Dispatching: <code>' . $controller->area . '> ' . get_class($controller) . '/' . $action
                . '</code>'
            );

            /* @var $controller Controller */

//                if (empty($this->_route_regex)) {
//                    Profiler::addLog('No route for <code>' . $this->controller . '</code>');
//                    return $controller->unknownAction();
//                } else {
            try {
                return $controller->run($route);
            } catch (DbError $e) {
                ob_get_clean();
                throw new Exception('DB Error: ' . $e->getMessage());
            } catch (Exception $e) {
                ob_get_clean();
                throw new Exception(
                    'Dispatch action: <er>' . get_class($controller) . '->' . $action . '</er> :<br/>'
                    . $e->getMessage());
            }
//                }
//            }
        }
    }

    /**
     * @param $route
     *
     * @return array (controller, action)
     */
    private function _createController($route)
    {
        $paths = array('/');

        if ($route != NULL) {
            foreach (array_keys($this->_loadedModules) as $path) {
                $paths[$path] = '/modules/' . $path . '/';
            }
            $classToCreate = $className = ucfirst($route->controller) . 'Controller';
            $areaName = '';
            if (!empty($route->area)) {
                $areaName = $route->area . '/';
                $classToCreate = ucfirst($route->area) . '_' . $className;
            }

//        var_dump($areaName);

            foreach (array('app' => TIKORI_ROOT, 'core' => TIKORI_FPATH) as $module => $source) {
                foreach ($paths as $path) {
                    //TODO: better list of folders created by module initializer
                    $file = $source . ($module == 'core' ? '' : '/' . $module) . $path . 'controllers/' . $areaName . $className . '.php';
                    if (file_exists($file)) {
                        try {
                            include_once $file;
                            $class = new $classToCreate($route->area);
                            $class->module = $module;
//                        $route->dispatch($class);
                            return (array($class, $route->action));
                        } catch (Exception $e) {
//                        var_dump($e);
                            //$class = new Controller($route);
                            //$class->forward404($route->area);
                        }
                    }
                }
            }
        }

        return array(new Controller(), NULL);
    }

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
     * @param bool         $core  Is it core path or not ?
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
     * @param string $item    Path to item, like name or name/name
     * @param mixed  $default Return this when nothing found
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

    private function _flatify($item, $array)
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

    private $_loadedModules = array();

    /**
     * Sets or unsets component
     *
     * @param string       $id Identifier of component
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

    /**
     * Returns base url for app
     *
     * @return string URL, like http://foo.bar/
     */
    public function baseUrl()
    {
        //return (isset(Core::app()->request->env)) ? Core::app()->request->env['tikori.base_url'] : '/';

        return (!empty($this->request)) ? $this->request->getBaseUrl(true) : '/';
    }
}

function __()
{
    $args = func_get_args();
    if (Core::app()->lang != NULL) {
        return Core::app()->lang->translate($args);
    }
}
