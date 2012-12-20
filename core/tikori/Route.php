<?php

class Route {
	// Defines the pattern of a <segment>

	const REGEX_KEY = '<([a-zA-Z0-9_]++)>';

	// What can be part of a <segment> value
	const REGEX_SEGMENT = '[^/.,;?\n]++';

	// What must be escaped in the route regex
	const REGEX_ESCAPE = '[.\\+*?[^\\]${}=!|]';

	/**
	 * @var  string  default action for all routes
	 */
	public static $default_action = 'index';
	public $area = '';

	/**
	 * @var  array
	 */
	protected static $_routes = array();

	public static function reconfigure() {
		Route::reset();
		// cfg route registers
		foreach (Core::app()->cfg('routes') as $key => $route) {
			Route::set($key, $route['expr'], (!empty($route['params'])) ? $route['params'] : array())->defaults($route['defaults']);
		}
		// default routes
		Route::set('tikori-admin', '<directory>(/<controller>(/<action>(/<id>)))(.html)', array('directory' => 'admin', 'id' => '.+'))
			->defaults(array(
				'controller' => 'admin',
				'action' => 'index',
			));
//		Route::set('tikori-default', '(<controller>(/<action>(/<id>)))(.html)')
		Route::set('tikori-default', '(<controller>(/<action>(/<id>)))')
			->defaults(array(
				'controller' => 'default',
				'action' => 'index',
			));
	}

	/**
	 * Stores a named route and returns it. The "action" will always be set to
	 * "index" if it is not defined.
	 *
	 *     Route::set('default', '(<controller>(/<action>(/<id>)))')
	 *         ->defaults(array(
	 *             'controller' => 'welcome',
	 *         ));
	 *
	 * @param   string  $name           route name
	 * @param   string  $uri_callback   URI pattern
	 * @param   array   $regex          regex patterns for route keys
	 * @return  Route
	 */
	public static function set($name, $uri_callback = NULL, $regex = NULL) {
		return Route::$_routes[$name] = new Route($uri_callback, $regex);
	}

	/**
	 * Retrieves a named route.
	 *
	 *     $route = Route::get('default');
	 *
	 * @param   string  $name   route name
	 * @return  Route
	 * @throws  Kohana_Exception
	 */
	public static function get($name) {
		if (!isset(Route::$_routes[$name])) {
			throw new Exception('The requested route does not exist: ' . $name);
		}

		return Route::$_routes[$name];
	}

	/**
	 * Retrieves all named routes.
	 *
	 *     $routes = Route::all();
	 *
	 * @return  array  routes by name
	 */
	public static function all() {
		return Route::$_routes;
	}

	public static function reset() {
		Route::$_routes = array();
	}

	/**
	 * Get the name of a route.
	 *
	 *     $name = Route::name($route)
	 *
	 * @param   Route   $route  instance
	 * @return  string
	 */
	public static function name(Route $route) {
		return array_search($route, Route::$_routes);
	}

	/**
	 * Create a URL from a route name. This is a shortcut for:
	 *
	 *     echo URL::site(Route::get($name)->uri($params), $protocol);
	 *
	 * @param   string  $name       route name
	 * @param   array   $params     URI parameters
	 * @param   mixed   $protocol   protocol string or boolean, adds protocol and domain
	 * @return  string
	 * @since   3.0.7
	 * @uses    URL::site
	 */
	public static function url($name, array $params = NULL, $protocol = NULL) {
		$route = Route::get($name);

		// Create a URI with the route and convert it to a URL
		if ($route->is_external())
			return Route::get($name)->uri($params);
		else
			return URL::site(Route::get($name)->uri($params), $protocol);
	}

	/**
	 * Returns the compiled regular expression for the route. This translates
	 * keys and optional groups to a proper PCRE regular expression.
	 *
	 *     $compiled = Route::compile(
	 *        '<controller>(/<action>(/<id>))',
	 *         array(
	 *           'controller' => '[a-z]+',
	 *           'id' => '\d+',
	 *         )
	 *     );
	 *
	 * @return  string
	 * @uses    Route::REGEX_ESCAPE
	 * @uses    Route::REGEX_SEGMENT
	 */
	public static function compile($uri, array $regex = NULL) {
		if (!is_string($uri))
			return;

		// The URI should be considered literal except for keys and optional parts
		// Escape everything preg_quote would escape except for : ( ) < >
		$expression = preg_replace('#' . Route::REGEX_ESCAPE . '#', '\\\\$0', $uri);

		if (strpos($expression, '(') !== FALSE) {
			// Make optional parts of the URI non-capturing and optional
			$expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
		}

		// Insert default regex for keys
		$expression = str_replace(array('<', '>'), array('(?P<', '>' . Route::REGEX_SEGMENT . ')'), $expression);

		if ($regex) {
			$search = $replace = array();
			foreach ($regex as $key => $value) {
				$search[] = "<$key>" . Route::REGEX_SEGMENT;
				$replace[] = "<$key>$value";
			}

			// Replace the default regex with the user-specified regex
			$expression = str_replace($search, $replace, $expression);
		}

		return '#^' . $expression . '$#uD';
	}

	/**
	 * Process URI
	 *
	 * @param   string  $uri     URI
	 * @param   array   $routes  Route
	 * @return  array
	 */
	public static function process_uri($uri, $routes = NULL) {
		$uri = trim($uri, '/');
		// Load routes
		$routes = (empty($routes)) ? Route::all() : $routes;
		$params = NULL;

		Log::addLog('Processing URI <tt>/' . $uri . '</tt> against ' . count($routes) . ' routes');

		/* @var $route Route */
		foreach ($routes as $name => $route) {
			// We found something suitable
			if ($params = $route->matches($uri)) {
				$route->params = $params;
				return /* clone */ $route;
//				return array(
//					'params' => $params,
//					'route' => $route,
//				);
			}
		}

		return NULL;
	}

	/**
	 * @var callback	The callback method for routes
	 */
	protected $_callback;

	/**
	 * @var string		route URI
	 */
	protected $_uri = '';

	/**
	 * @var array
	 */
	protected $_regex = array();

	/**
	 * @var array
	 */
	protected $_defaults = array('action' => 'index', 'host' => FALSE);

	/**
	 * @var string
	 */
	protected $_route_regex;

	/**
	 * @var	array 
	 */
	public $params = array();

	/**
	 * Creates a new route. Sets the URI and regular expressions for keys.
	 * Routes should always be created with [Route::set] or they will not
	 * be properly stored.
	 *
	 *     $route = new Route($uri, $regex);
	 *
	 * The $uri parameter can either be a string for basic regex matching or it
	 * can be a valid callback or anonymous function (php 5.3+). If you use a
	 * callback or anonymous function, your method should return an array
	 * containing the proper keys for the route. If you want the route to be
	 * "reversable", you need pass the route string as the third parameter.
	 *
	 *     $route = new Route(function($uri)
	 *     {
	 *     	if (list($controller, $action, $param) = explode('/', $uri) AND $controller == 'foo' AND $action == 'bar')
	 *     	{
	 *     		return array(
	 *     			'controller' => 'foobar',
	 *     			'action' => $action,
	 *     			'id' => $param,
	 *     		);
	 *     	},
	 *     	'foo/bar/<id>'
	 *     });
	 *
	 * @param   mixed   $uri    route URI pattern or lambda/callback function
	 * @param   array   $regex  key patterns
	 * @return  void
	 * @uses    Route::_compile
	 */
	public function __construct($uri = NULL, $regex = NULL) {
		if ($uri === NULL) {
			// Assume the route is from cache
			return;
		}

		if (!is_string($uri) AND is_callable($uri)) {
			$this->_callback = $uri;
			$this->_uri = $regex;
			$regex = NULL;
		} elseif (!empty($uri)) {
			$this->_uri = $uri;
		}

		if (!empty($regex)) {
			$this->_regex = $regex;
		}

		// Store the compiled regex locally
		$this->_route_regex = Route::compile($uri, $regex);
	}

	/**
	 * Provides default values for keys when they are not present. The default
	 * action will always be "index" unless it is overloaded here.
	 *
	 *     $route->defaults(array(
	 *         'controller' => 'welcome',
	 *         'action'     => 'index'
	 *     ));
	 *
	 * If no parameter is passed, this method will act as a getter.
	 *
	 * @param   array   $defaults   key values
	 * @return  $this or array
	 */
	public function defaults(array $defaults = NULL) {
		if ($defaults === NULL) {
			return $this->_defaults;
		}

		$this->_defaults = $defaults;

		return $this;
	}

	/**
	 * Tests if the route matches a given URI. A successful match will return
	 * all of the routed parameters as an array. A failed match will return
	 * boolean FALSE.
	 *
	 *     // Params: controller = users, action = edit, id = 10
	 *     $params = $route->matches('users/edit/10');
	 *
	 * This method should almost always be used within an if/else block:
	 *
	 *     if ($params = $route->matches($uri))
	 *     {
	 *         // Parse the parameters
	 *     }
	 *
	 * @param   string  $uri    URI to match
	 * @return  array   on success
	 * @return  FALSE   on failure
	 */
	public function matches($uri) {
		if ($this->_callback) {
			$closure = $this->_callback;
			$params = call_user_func($closure, $uri);

			if (!is_array($params))
				return FALSE;
		}
		else {
			if (!preg_match($this->_route_regex, $uri, $matches))
				return FALSE;

			$params = array();
			foreach ($matches as $key => $value) {
				if (is_int($key)) {
					// Skip all unnamed keys
					continue;
				}

				// Set the value for all matched keys
				$params[$key] = $value;
			}
		}

		foreach ($this->_defaults as $key => $value) {
			if (!isset($params[$key]) OR $params[$key] === '') {
				// Set default values for any key that was not matched
				$params[$key] = $value;
			}
		}

		return $params;
	}

	/**
	 * Generates a URI for the current route based on the parameters given.
	 *
	 *     // Using the "default" route: "users/profile/10"
	 *     $route->uri(array(
	 *         'controller' => 'users',
	 *         'action'     => 'profile',
	 *         'id'         => '10'
	 *     ));
	 *
	 * @param   array   $params URI parameters
	 * @return  string
	 * @throws  Kohana_Exception
	 * @uses    Route::REGEX_Key
	 */
	public function uri(array $params = NULL) {
		// Start with the routed URI
		$uri = $this->_uri;

		if (strpos($uri, '<') === FALSE AND strpos($uri, '(') === FALSE) {
			// This is a static route, no need to replace anything

			if (!$this->is_external())
				return $uri;

			// If the localhost setting does not have a protocol
			if (strpos($this->_defaults['host'], '://') === FALSE) {
				// Use the default defined protocol
				$params['host'] = Route::$default_protocol . $this->_defaults['host'];
			} else {
				// Use the supplied host with protocol
				$params['host'] = $this->_defaults['host'];
			}

			// Compile the final uri and return it
			return rtrim($params['host'], '/') . '/' . $uri;
		}

		while (preg_match('#\([^()]++\)#', $uri, $match)) {
			// Search for the matched value
			$search = $match[0];

			// Remove the parenthesis from the match as the replace
			$replace = substr($match[0], 1, -1);

			while (preg_match('#' . Route::REGEX_KEY . '#', $replace, $match)) {
				list($key, $param) = $match;

				if (isset($params[$param])) {
					// Replace the key with the parameter value
					$replace = str_replace($key, $params[$param], $replace);
				} else {
					// This group has missing parameters
					$replace = '';
					break;
				}
			}

			// Replace the group in the URI
			$uri = str_replace($search, $replace, $uri);
		}

		while (preg_match('#' . Route::REGEX_KEY . '#', $uri, $match)) {
			list($key, $param) = $match;

			if (!isset($params[$param])) {
				// Look for a default
				if (isset($this->_defaults[$param])) {
					$params[$param] = $this->_defaults[$param];
				} else {
					// Ungrouped parameters are required
					throw new Kohana_Exception('Required route parameter not passed: :param', array(
						':param' => $param,
					));
				}
			}

			$uri = str_replace($key, $params[$param], $uri);
		}

		// Trim all extra slashes from the URI
		$uri = preg_replace('#//+#', '/', rtrim($uri, '/'));

		if ($this->is_external()) {
			// Need to add the host to the URI
			$host = $this->_defaults['host'];

			if (strpos($host, '://') === FALSE) {
				// Use the default defined protocol
				$host = Route::$default_protocol . $host;
			}

			// Clean up the host and prepend it to the URI
			$uri = rtrim($host, '/') . '/' . $uri;
		}

		return $uri;
	}

	public function dispatch() {
		// check for app
		# don't need to check that anymore, default controller can be in core
//		if (!file_exists(Core::app()->appDir)) {
//			throw new Exception('app/ path not found');
//		}
		// get controller first
		$controller = null;
		try {
			$class = $this->getControllerClassName();
			$controller = new $class;
			/* @var $controller Controller */
			$controller->setController($this->getController());
			$controller->setAction($this->getAction());
			$controller->setParams($this->params);
		} catch (Exception $e) {
			throw new RouteNotFoundException('Dispatch controller: <er>' . $this->getDirectory() . $this->getController() . '/' . $this->getAction() . '</er>: ' . $e->getMessage());
		}

		if (!method_exists($controller, $this->getActionMethodName())) {
			$this->setAction('default');
		}

		try {
			$reflection = new ReflectionClass($controller);
			$finalParams = array();

			try {
				$method = $reflection->getMethod($this->getActionMethodName());
			} catch (Exception $ref) {
				throw new RouteNotFoundException('Unknown action');
			}
			/* @var $method ReflectionMethod */
			if ($method->getNumberOfRequiredParameters() > 0) {
//				var_dump($method->getNumberOfRequiredParameters());

				foreach ($method->getParameters() as $paramObject) {
					/* @var $paramObject ReflectionParameter */

					if ($paramObject->isOptional() === false and empty($this->params[$paramObject->name])) {
						throw new RouteNotFoundException('Not enough arguments or wrong argument name [' . $paramObject->name . ']');
					}

					$finalParams[] = (empty($this->params[$paramObject->name])) ? null : $this->params[$paramObject->name];
				}
//				var_dump($this->params);
//				var_dump($method->getParameters());
//				if (empty($this->params['id'])) {
//					throw new RouteNotFoundException('Not enough arguments');
//				} else {
//					$finalParams[] = $this->params['id'];
//				}
			}

//			$params = explode('/', $);
//			if (count($th) < $method->getNumberOfRequiredParameters()) {
//				// 404 my dear... not enough required params
//				throw new Exception('Less params than required');
//			}
//			foreach ($method->getParameters() as $param) {
//				/* @var $param ReflectionParameter */
//				if (!empty($params[$param->name])) {
//					$finalParams[$param->name] = $params[$param->name];
//				} else {
//					if ($param->isDefaultValueAvailable()) {
//						continue;
//					} else {
//						throw new Exception('At least one of required params isn\'t available');
//					}
//				}
//			}

			Log::addLog('Calling controller: <tt>' . $this->getControllerClassName() . '::' . $this->getActionMethodName() . '</tt>');

			ob_start();
			call_user_func_array(array($controller, $this->getAction() . 'Action'), $finalParams);
			$response = ob_get_clean();

			Log::addLog('Owerwriting body using last controller action');

			Core::app()->response->body($response);
		} catch (DbError $e) {
			ob_get_clean();
			throw new Exception('DB Error: ' . $e->getMessage());
		} catch (Exception $e) {
			ob_get_clean();
			throw new Exception('Dispatch action: <er>' . $this->getController() . '->' . $this->getAction() . '</er> :<br/>' . $e->getMessage());
		}
	}

	public function getController() {
		return $this->_getParam('controller');
	}

	public function getControllerClassName() {
		return ucfirst($this->getController()) . 'Controller';
	}

	public function getAction() {
		return $this->_getParam('action', 'index');
	}

	public function getActionMethodName() {
		return $this->getAction() . 'Action';
	}

	public function setAction($action) {
		return $this->params['action'] = $action;
	}

	public function getDirectory() {
		$dir = $this->_getParam('directory', '');
		return (empty($dir)) ? '' : $dir . '/';
	}

	private function _getParam($key, $def = 'default') {
		if (!empty($this->params[$key])) {
			return $this->params[$key];
		} else if (!empty($this->_defaults[$key])) {
			return $this->_defaults[$key];
		} else {
			return $def;
		}
	}

}
