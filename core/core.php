<?php

class Core {

	const MODE_DEBUG = -1;
	const MODE_DEV = 0;
	const MODE_PROD = 1;

	private static $_config = array();
	public static $appDir = '';

	/**
	 * @var Request 
	 */
	public static $request = null;

	/**
	 * @var Response
	 */
	public static $response = null;
	public static $mode = 0;

	/**
	 * @var Route
	 */
	public static $route = null;

	public static function run($path = '', $config = 'default') {
		if (empty($path)) {
			self::$appDir = dirname(__FILE__);
		} else {
			self::$appDir = $path;
		}
		spl_autoload_register(array('Core', 'autoload'), true);
		set_exception_handler(array('Core', 'exh'));

		self::reconfigure(file_get_contents(self::$appDir . '/app/config/' . $config . '.json'));

		// request
		self::$request = new Request();
		self::$response = new Response();
		self::$route = Route::process_uri(self::$request->getRouterPath());

		try {
			if (self::$route == null)
				throw new E404Exception('Not found');
			self::$route->dispatch();
		} catch (E404Exception $e) {
			$view = new View();
			$body = $view->render('error404', array('content' => 'Requested url cannot be found'), true);
			self::$response->status(404);
			self::$response->write($body, true);
		} catch (Exception $e) {
			$view = new View();
			$body = $view->render('error404', array('content' => $e->getMessage()), true);
			self::$response->status(404);
			self::$response->write($body, true);
		}

		list($status, $header, $body) = self::$response->finalize();

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

	/**
	 * Sets application mode
	 * @return int (Core::MODE_DEBUG, Core::MODE_DEV, Core::MODE_PROD)
	 */
	public static function getMode() {
		if (!isset(self::$_config['mode'])) {
			if (isset($_ENV['TIKORI_MODE'])) {
				self::$mode = $_ENV['TIKORI_MODE'];
			} else {
				$envMode = getenv('TIKORI_MODE');
				if ($envMode !== false) {
					self::$mode = $envMode;
				} else {
					self::$mode = (!empty(self::$_config['mode'])) ? self::$_config['mode'] : self::MODE_DEV;
				}
			}
		}

		return self::$mode;
	}

	/**
	 * Reconfigures application using json string or array
	 * @param array|string $config
	 */
	public static function reconfigure($config) {
		if (is_string($config)) {
			self::$_config = json_decode($config, true);
		} else if (is_array($config)) {
			self::$_config = $config;
		} else {
			die('Config errror.');
		}

		Route::reset();
		// cfg route registers
		foreach (self::$_config['routes'] as $key => $route) {
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

		self::getMode();
	}

	public static function cfg($key, $val = null) {
		if ($val === null) {
			if (array_key_exists($key, self::$_config)) {
				return self::$_config[$key];
			} return null;
		} else {
			self::$_config[$key] = $val;
		}
	}
	
	public static function exh(Exception $exception) {
		echo Error::display($exception);
		die();
	}

	public static function autoload($class) {
		$file = $class;
		if ($class !== 'Controller') {
			$file = str_replace('Controller', '', $class);
		}
		foreach (array('app/controllers', 'core', 'app/models') as $dir) {
			$filename = self::$appDir . '/' . $dir . '/' . strtolower($file) . '.php';
			if (file_exists($filename)) {
				require $filename;
				return true;
			}
		}

		throw new Exception("Cannot autoload class " . $class);
	}

}
