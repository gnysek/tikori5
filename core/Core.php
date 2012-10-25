<?php

defined('TIKORI_STARTED') or define('TIKORI_STARTED', microtime());
defined('TIKORI_DEBUG') or define('TIKORI_DEBUG', false);
#defined('TIKORI_PATH') or define('TIKORI_PATH', dirname(__FILE__));

class Core {

	const MODE_DEBUG = -1;
	const MODE_DEV = 0;
	const MODE_PROD = 1;

	/**
	 * @var Core Main app class 
	 */
	private static $_app = null;

	/**
	 * Returns main app
	 * @return Core
	 */
	public static function app() {
		return self::$_app;
	}

	public static function asssignApp($app) {
		if (self::$_app === null) {
			self::$_app = $app;
		} else {
			throw new Exception('Tikori5 cannot be run more than once!');
		}
	}

	public static function run($path = '', $config = 'default') {
		if (self::$_app === null) {
			$core = new Tikori($path, $config);
		} else {
			self::asssignApp(null);
		}
	}

	public static function autoload($class) {
		$search = str_replace('_', '/', $class);

		preg_match('#(.*)/(.*)#i', $search, $match);
		if (!empty($match)) {
			$search = strtolower($match[1]) . '/' . $match[2];
		} else {
			$search = ucfirst($class);
		}

		$search .= '.php';

		foreach (Core::app()->autoloadPaths as $dir) {
			$filename = $dir . '/' . $search;
			if (file_exists($filename)) {
				require $filename;
				return true;
			}
		}

		throw new Exception("Cannot autoload class " . $class . '[' . $search . ']');
	}

}

/**
 * @property string $appDir Application main directory (where index.php is)
 * @property Request $request
 * @property Response $request
 * @property int $mode Core::MODE_XXX
 * @property array $autoloadPaths Array of autoload paths
 */
class Tikori {

	private $_config = array();
	public $appDir = '';

	/**
	 * @var Request 
	 */
	public $request = null;

	/**
	 * @var Response
	 */
	public $response = null;
	public $mode = 0;
	public $autoloadPaths = array();

	/**
	 * @var Route
	 */
	public $route = null;

	public function __construct($path = '', $config = 'default') {
		$this->init($path, $config);
	}

	public function init($path = '', $config = 'default') {
		Core::asssignApp($this);

		if (empty($path)) {
			$this->appDir = dirname(__FILE__);
		} else {
			$this->appDir = $path;
		}

		// register autoloads
		spl_autoload_register(array('Core', 'autoload'), true);
		$this->registerAutoloadPaths();

		// register error handlers
		Error::registerErrors();

		$this->reconfigure(file_get_contents($this->appDir . '/app/config/' . $config . '.json'));

		// request
		$this->request = new Request();
		$this->response = new Response();
		$this->route = Route::process_uri($this->request->getRouterPath());

		try {
			if ($this->route == null)
				throw new RouteNotFoundException('Not found');
			$this->route->dispatch();
		} catch (RouteNotFoundException $e) {
			$view = new Controller();
			$body = $view->renderPartial('error404', array('content' => 'Requested url cannot be found', 'debug' => $e->getMessage()), true);
			$this->response->status(404);
			$this->response->write($body, true);
		} catch (Exception $e) {
			$view = new Controller();
			$body = $view->renderPartial('error404', array('content' => $e->getMessage()), true);
			$this->response->status(404);
			$this->response->write($body, true);
		}

		list($status, $header, $body) = $this->response->finalize();

		//Send headers
		if (headers_sent() === false) {
			//Send status
			if (strpos(PHP_SAPI, 'cgi') === 0) {
				header(sprintf('Status: %s', Response::getMessageForCode($status)));
			} else {
				header(sprintf('HTTP/%s %s', /* $this->config('http.version') */ '1.1', Response::getMessageForCode($status)));
			}

			//Send headers
			foreach ($header as $name => $value) {
				$hValues = explode("\n", $value);
				foreach ($hValues as $hVal) {
					header("$name: $hVal", false);
				}
			}
		}

		echo $body;
	}

	public function registerAutoloadPaths() {
		$this->addAutoloadPaths(array(
			'core',
			'core/tools/',
			'core/db/',
			'app',
			'app/config',
			'app/controllers',
			'app/models',
			'app/modules',
		));
	}

	public function addAutoloadPaths($paths) {
		if (is_string($paths)) {
			return $this->addAutoloadPaths(array($paths));
		} else {
			foreach ($paths as $k => $dir) {
				$dir = Core::app()->appDir . '/' . $dir;
				if (!in_array($dir, $this->autoloadPaths)) {
					$this->autoloadPaths[] = $dir;
				}
			}
		}
	}

	/**
	 * Sets application mode
	 * @return int (Core::MODE_DEBUG, Core::MODE_DEV, Core::MODE_PROD)
	 */
	public function getMode() {
		if (!isset($this->_config['mode'])) {
			if (isset($_ENV['TIKORI_MODE'])) {
				$this->mode = $_ENV['TIKORI_MODE'];
			} else {
				$envMode = getenv('TIKORI_MODE');
				if ($envMode !== false) {
					$this->mode = $envMode;
				} else {
					$this->mode = (!empty($this->_config['mode'])) ? $this->_config['mode'] : Core::MODE_DEV;
				}
			}
		}

		return $this->mode;
	}

	/**
	 * Reconfigures application using json string or array
	 * @param array|string $config
	 */
	public function reconfigure($config) {
		if (is_string($config)) {
			$this->_config = json_decode($config, true);
		} else if (is_array($config)) {
			$this->_config = $config;
		} else {
			die('Config errror.');
		}

		Route::reset();
		// cfg route registers
		foreach ($this->_config['routes'] as $key => $route) {
			Route::set($key, $route['expr'], (!empty($route['params'])) ? $route['params'] : array())->defaults($route['defaults']);
		}
		// default routes
		Route::set('tikori-admin', '<directory>(/<controller>(/<action>(/<id>)))(.html)', array('directory' => 'admin', 'id' => '.+'))
			->defaults(array(
				'controller' => 'admin',
				'action' => 'index',
			));
		Route::set('tikori-default', '(<controller>(/<action>(/<id>)))(.html)')
			->defaults(array(
				'controller' => 'default',
				'action' => 'index',
			));

		$this->getMode();
	}

	public function cfg($key, $val = null) {
		if ($val === null) {
			if (array_key_exists($key, $this->_config)) {
				return $this->_config[$key];
			} return null;
		} else {
			$this->_config[$key] = $val;
		}
	}

	public function baseUrl() {
		return Core::app()->request->env['tikori.base_url'];
	}

}
