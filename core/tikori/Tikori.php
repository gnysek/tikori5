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

        Profiler::addLog('Registered autoload');

        // register error handlers
        Error::registerErrors();
        register_shutdown_function(array('Core', 'shutdown_handler'));
        Profiler::addLog('Registered errors');

        if (ini_get('short_open_tag') != 1) {
            throw new Exception('Tikori 5 requires short_open_tag enabled!');
        }

//		$this->defaultCfg();

        // config
        $this->reconfigure($config);

        // enable cache, we need that for config
        $this->setModule('cache', new Cache());
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
            $this->setModule('db', $db);
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
        $this->loadLanguages();

        // process route
        $this->route = Route::process_uri($this->request->getRouterPath());

        Profiler::addLog('Route processed - is ' . (($this->route == NULL) ? 'not found' : 'found'));

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
            foreach (array_keys($this->_m) as $path) {
                $paths[$path] = '/modules/' . $path . '/';
            }
            $classToCreate = $className = ucfirst($route->controller) . 'Controller';
            $areaName = '';
            if (!empty($route->area)) {
                $areaName = $route->area . '/';
                $classToCreate = ucfirst($route->area) . '_' . $className;
            }

//        var_dump($areaName);

            foreach (array('app', 'core') as $module => $source) {
                foreach ($paths as $path) {
                    $file = $source . $path . 'controllers/' . $areaName . $className . '.php';
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
        foreach (array(true, false) as $includeCoreDir) {
            $this->addAutoloadPaths(
                array(
                     'modules/' . $module,
                     'modules/' . $module . '/config',
                     'modules/' . $module . '/controllers',
                     'modules/' . $module . '/models',
                     'modules/' . $module . '/views',
                     'modules/' . $module . '/widgets',
                ), $includeCoreDir
            );
        }

        $moduleClass = ucfirst($module) . 'Module';
        if (Core::autoload($moduleClass, true)) {
            $class = new $moduleClass;
            $this->setModule($module, $class);
        }
    }

    /**
     * Registers autoload paths for class searching
     */
    public function registerAutoloadPaths()
    {
        // core directory - can be shared on server :) false then true
        foreach (array(true, false) as $i) {
            $this->addAutoloadPaths(
                array(
                     '',
                     'controllers',
                     'models',
                     //				'modules',
                     'db',
                     'helpers',
                     'widgets',
                     'tikori',
                ), $i
            );
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
        $this->_config = new \Config(array(
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
     * @param string $item
     * @param mixed  $default
     *
     * @return Config|array
     */
    public function cfg($item = NULL, $default = NULL)
    {
        if ($this->_config === NULL) {
            $this->_config = new Config();
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

    private $_m = array();

    /**
     * Sets or unsets module
     *
     * @param string       $id Identifier of component
     * @param TModule|null $module
     */
    public function setModule($id, $module)
    {
        if ($module === NULL) {
            unset($this->_m[$id]);
        } else {
            $this->_m[$id] = $module;
            if (!$module->isInitialized()) {
                $module->init();
            }
        }
    }

    /**
     * @param array $modules
     */
    public function setModules(array $modules)
    {
        foreach ($modules as $id => $module) {
            $this->setModule($id, $module);
        }
    }

    public function __get($value)
    {
        if (empty($this->_m[$value])) {
            return NULL;
        } else {
            return $this->_m[$value];
        }
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

    public $languages = array();
    public $translations = array();
    public $defaultLanguage = 'en';

    public function loadLanguages()
    {
        if (!$this->cfg('languages')) {
            return;
        }

        $avaliableLanguages = $this->cfg('languages/list');

        if (count($avaliableLanguages) < 1) {
            return; //no languages?
        }

        $this->defaultLanguage = $avaliableLanguages[0];

        // setup current language
        if ($this->cfg('languages/type') == 'subdomains') {
            $subdomains = $this->request->env['tikori.subdomains'];
            if (count($subdomains)) {
                $subdomain = $subdomains[0];
                foreach ($avaliableLanguages as $lang) {
                    if ($lang == $subdomain) {
                        $this->defaultLanguage = $lang;
                        break;
                    }
                }
            }
        } else {
            //areas, todo
        }

        foreach (array('core', 'app') as $namespace) {

            if ($namespace == 'core') {
                $autodir = TIKORI_FPATH;
            } else {
                $autodir = rtrim(TIKORI_ROOT . DIRECTORY_SEPARATOR . $namespace, '/');
            }
            $files = glob($autodir . '/locale/*.php');

            foreach ($files as $filename) {

                $language = preg_replace('#([a-z]+)\.php#i', '$1', basename($filename));
                if (!in_array($language, $this->languages)) {
                    $this->languages[] = $language;
                    $this->translations[$language] = array();
                }

                $file = fopen($filename, 'r');
                $lang = array();
                while ($data = fgetcsv($file, NULL, ',')) {
                    if (count($data) == 2) {
                        $this->translations[$language][$data[0]] = $data[1];
                    }
                }
                fclose($file);
            }
        }
    }

    public function __() {
        $args = func_get_args();
        return $this->translate($args);
    }

    public function translate($args)
    {
        if (empty($args)) {
            return '';
        }

        $text = $args[0];

        if (in_array($this->defaultLanguage, $this->languages)) {
            if (array_key_exists($args[0], $this->translations[$this->defaultLanguage])) {
                $text = $this->translations[$this->defaultLanguage][$text];
            }
        }

        $args = array_slice($args, 1);

        if (count($args) > 0) {
            foreach($args as $v) {
                $text = str_replace('%s',$v, $text);
            }
        }

        return $text;
    }
}

function __()
{
    $args = func_get_args();
    return Core::app()->translate($args);
}
