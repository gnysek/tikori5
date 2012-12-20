<?php

/**
 * @property string $appDir Application main directory (where index.php is)
 * @property Request $request
 * @property Response $request
 * @property int $mode Core::MODE_XXX
 * @property array $autoloadPaths Array of autoload paths
 */
class Tikori {

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

	public function __construct($path = '', $config = 'default') {
		$this->init($path, $config);
	}

	/**
	 * It runs application. Whole magic is here, abracadabra!
	 * Echoes results, throw exceptions and 404s, redirects etc.
	 * 
	 * @param type $path Path to APP parent dir
	 * @param string $config config file name without .json, usually 'default'
	 * @throws RouteNotFoundException
	 */
	public function init($path = '', $config = 'default') {
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

		$this->reconfigure($config);
		Route::reconfigure();
		Log::addLog('Reconfigured');

		// configure modules
		$modules = $this->cfg('modules');
		if (!empty($modules)) {
			foreach ($this->cfg('modules') as $module => $config) {
				foreach (array(true, false) as $includeCoreDir) {
					$this->addAutoloadPaths(array(
						'modules/' . $module,
						'modules/' . $module . '/config',
						'modules/' . $module . '/controllers',
						'modules/' . $module . '/models',
						'modules/' . $module . '/views',
						), $includeCoreDir);
				}
				$moduleClass = ucfirst($module) . 'Module';
				if (Core::autoload($moduleClass, false)) {
					$class = new $moduleClass;
					if (method_exists($class, 'init')) {
						$class->init();
					}
				}
			}
		}

		// request
		$this->request = new Request();
		Log::addLog('Request created');
		$this->response = new Response();
		Log::addLog('Response created');

		// process route
		$this->route = Route::process_uri($this->request->getRouterPath());

		try {
			if ($this->route == null)
				throw new RouteNotFoundException('Not found');
			$this->route->dispatch();
		} catch (RouteNotFoundException $e) {
			$view = new Controller();
			$body = $view->renderPartial('error.404', array('content' => 'Requested url cannot be found', 'debug' => $e->getMessage()), true);
			$this->response->status(404);
			$this->response->write($body, true);
		}/* catch (Exception $e) {
		  $view = new Controller();
		  $body = $view->renderPartial('error.404', array('content' => $e->getMessage()), true);
		  $this->response->status(404);
		  $this->response->write($body, true);
		  } */

		Log::addLog('Route handled');

		Log::addLog('Finalizing response');
		list($status, $header, $body) = $this->response->finalize();
		Log::addLog('Response finalized');

		//Send headers
		if (headers_sent() === false) {
			//Send status
			if (strpos(PHP_SAPI, 'cgi') === 0) {
				header(sprintf('Status: %s', Response::getMessageForCode($status)));
			} else {
				header(sprintf('HTTP/%s %s', '1.1', Response::getMessageForCode($status)), false);
			}

			//Send headers
			foreach ($header as $name => $value) {
				$hValues = explode("\n", $value);
				foreach ($hValues as $hVal) {
					header("$name: $hVal", false);
				}
			}
		}

		Log::addLog('Headers:<br/>' . implode('<br/>', headers_list()));

		echo $body;

		Log::addLog('Finishing application');
		if ($this->mode != Core::MODE_PROD) {
			echo Log::getLogs();
		}
		return true;
	}

	/**
	 * Registers autoload paths for class searching
	 */
	public function registerAutoloadPaths() {
		// core directory - can be shared on server :) false then true
		foreach (array(true, false) as $i) {
			$this->addAutoloadPaths(array(
				'',
				'controllers',
				'models',
//				'modules',
				'db',
				'helpers',
				'widgets',
				'tikori',
				), $i);
		}

//		var_dump($this->autoloadPaths);
//		die();
	}

	/**
	 * Adds paths for autload if you need some another paths than default.
	 * Usually it should be set in config and not called from app.
	 * 
	 * @param string|array $paths Paths to add as array values
	 * @return type
	 */
	public function addAutoloadPaths($paths, $core = false) {
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
	 * @return int (Core::MODE_DEBUG, Core::MODE_DEV, Core::MODE_PROD)
	 */
	public function getMode() {
		if (/* $this->cfg('mode') */ $this->mode === null) {
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

	public function defaultCfg() {
		$this->_config = new Config(array(
				'appname' => 'Unknown application',
//				'url' => DefC_Url::getDefValues(),
//				'db' => DefC_Db::getDefValues(),
			));
	}

	/**
	 * Reconfigures application using json string or array
	 * @param array|string $config
	 */
	public function reconfigure($config) {
		$this->cfg()->load($config);
		$this->getMode();
	}

	/**
	 * @return Config
	 */
	public function cfg($item = null, $default = null) {
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

	public function flatcfg($item = null, $default = null) {
		if ($item === null) {
			return $this->_flatify($item, $this->_config);
		} else {
			return $this->_flatify($item, $this->_config->get($item, $default));
		}
	}

	private function _flatify($item, $array) {
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
	public function baseUrl() {
		//return (isset(Core::app()->request->env)) ? Core::app()->request->env['tikori.base_url'] : '/';

		return (!empty($this->request)) ? $this->request->getBaseUrl(true) : '/';
	}

}
