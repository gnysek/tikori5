<?php

/**
 * @property string     $appDir        Application main directory (where index.php is)
 * @property Request    $request
 * @property Response   $response
 * @property Route      $route
 * @property int        $mode          Core::MODE_XXX
 * @property DbAbstract $db
 * @property Cache      $cache         Cache module
 * @property array      $autoloadPaths Array of autoload paths
 */
class Tikori
{

    const EVENT_BEFORE_DISPATCH = 'before_dispatch';
    const EVENT_AFTER_DISPATCH = 'after_dispatch';
    /**
     * @var Config
     */
    private $_config = null;
    public $appDir = '';
    public $coreDir = '';

    /**
     * @var Request
     */
    public $request = null;

    /**
     * @var Response
     */
    public $response = null;
    public $mode = null;
    public $autoloadPaths = array();

    /**
     * @var Route
     */
    public $route = null;

    public function __construct($path = '', $config = 'default')
    {
        $this->init($path, $config);
    }

    /**
     * It runs application. Whole magic is here, abracadabra!
     * Echoes results, throw exceptions and 404s, redirects etc.
     *
     * @param type   $path   Path to APP parent dir
     * @param string $config config file name without .json, usually 'default'
     *
     * @throws RouteNotFoundException
     */
    public function init($path = '', $config = 'default')
    {
        // assign reference
        Core::asssignApp($this);

        // set directories
        $this->appDir = (empty($path)) ? dirname(__FILE__) . '../app' : $path . '/app';
        $this->coreDir = TIKORI_CPATH;

        // register autoloads
        spl_autoload_register(array('Core', 'autoload'));
        $this->registerAutoloadPaths();

        Log::addLog('Registered autoload');

        // register error handlers
        Error::registerErrors();
        Log::addLog('Registered errors');

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
        Route::set('tikori-default', '(<controller>(/<action>(/<id>)))')
            ->defaults(
                array(
                     'controller' => ($this->cfg('default') !== null) ? $this->cfg('default') : 'default',
                     'action'     => 'index',
                )
            );

        // request
        $this->request = new Request();
        Log::addLog('Request created');
        $this->response = new Response();
        Log::addLog('Response created');

        // process route
        $this->route = Route::process_uri($this->request->getRouterPath());

        Core::event(self::EVENT_BEFORE_DISPATCH);

        if ($this->route == null) {
            //$this->route = new Route();
            Controller::forward404();
        } else {
            $this->route->dispatch();
        }

        Core::event(self::EVENT_AFTER_DISPATCH);

        Log::addLog('Route handled');

        $this->response->send();

//		Log::addLog('Finalizing response');
//		list($status, $header, $body) = $this->response->finalize();
//		Log::addLog('Response finalized');
//
//		//Send headers
//		if (headers_sent() === false) {
//			//Send status
//			if (strpos(PHP_SAPI, 'cgi') === 0) {
//				header(sprintf('Status: %s', Response::getMessageForCode($status)));
//			} else {
//				header(sprintf('HTTP/%s %s', '1.1', Response::getMessageForCode($status)), false);
//			}
//
//			//Send headers
//			foreach ($header as $name => $value) {
//				$hValues = explode("\n", $value);
//				foreach ($hValues as $hVal) {
//					header("$name: $hVal", false);
//				}
//			}
//		}
//
//		Log::addLog('Headers:<br/>' . implode('<br/>', headers_list()));
//
//		echo $body;

        Log::addLog('Finishing application');
        if ($this->mode != Core::MODE_PROD) {
            echo Log::getLogs();
        }
        return true;
    }

    public function preloadModule($module, $config = null)
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
     * @return type
     */
    public function addAutoloadPaths($paths, $core = false)
    {
        if (is_string($paths)) {
            return $this->addAutoloadPaths(array($paths), $core);
        } else {
            foreach ($paths as $k => $dir) {
                $dir = rtrim((($core) ? $this->coreDir : $this->appDir) . '/' . $dir, '/');
                if (!in_array($dir, $this->autoloadPaths) and file_exists($dir)) {
//					$this->autoloadPaths[] = $dir;
                    if (!empty($this->autoloadPaths)) {
                        array_unshift($this->autoloadPaths, $dir);
                    } else {
                        $this->autoloadPaths[] = $dir;
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
            $this->mode === null
        ) {
            if (isset($_ENV['TIKORI_MODE'])) {
                $this->mode = $_ENV['TIKORI_MODE'];
            } else {
                $envMode = getenv('TIKORI_MODE');
                if ($envMode !== false) {
                    $this->mode = $envMode;
                } else {
                    $this->mode = ($this->cfg('mode') === null) ? Core::MODE_DEV : $this->cfg('mode');
                }
            }
        }

        return $this->mode;
    }

    public function defaultCfg()
    {
        $this->_config = new Config(array(
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
     * @return Config
     */
    public function cfg($item = null, $default = null)
    {
        if ($this->_config === null) {
            $this->_config = new Config();
        }

        if ($item === null) {
            return $this->_config;
        } else {

            if (substr($item, strlen($item) - 2, 2) === '/*') {
                return $this->cfg(substr($item, 0, strlen($item) - 2));
            }
            // maybe later add code to search bt path/to/node/somet* ?

            return $this->_config->get($item, $default);
        }
    }

    public function flatcfg($item = null, $default = null)
    {
        if ($item === null) {
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
        if ($module === null) {
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
            return null;
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

}
