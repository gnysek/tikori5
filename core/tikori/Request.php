<?php

/**
 * @param string $request_method (GET|POST|PUT|DELETE)
 * @param string $script_name
 */
class Request {

	const METHOD_HEAD = 'HEAD';
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_OVERRIDE = '_METHOD';

	public $env = array();

	public static function mock() {
		return array(
			'REQUEST_METHOD' => 'GET',
			'SCRIPT_NAME' => '',
			'PATH_INFO' => '',
			'QUERY_STRING' => '',
			'SERVER_NAME' => 'localhost',
			'SERVER_PORT' => 80,
			'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
			'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'USER_AGENT' => 'Tikori Framework',
			'REMOTE_ADDR' => '127.0.0.1',
			'tikori.url_scheme' => 'http',
			'route' => array(),
//			'tikori.input' => ''
		);
	}

	/**
	 * Is this an AJAX request?
	 * @return bool
	 */
	public function isAjax() {
		if ($this->params('isajax')) {
			return true;
		} elseif (isset($this->env['X_REQUESTED_WITH']) && $this->env['X_REQUESTED_WITH'] === 'XMLHttpRequest') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Fetch GET and POST data
	 *
	 * This method returns a union of GET and POST data as a key-value array, or the value
	 * of the array key if requested; if the array key does not exist, NULL is returned.
	 *
	 * @param  string           $key
	 * @return array|mixed|null
	 */
	public function params($key = null) {
		$union = array_merge($this->get(), $this->post());
		if ($key) {
			if (isset($union[$key])) {
				return $union[$key];
			} else {
				return null;
			}
		} else {
			return $union;
		}
	}

	public function get() {
		return array();
	}

	public function post() {
		return array();
	}

	public function __construct() {
		$env = array();
		//The HTTP request method
		$env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

		//The IP
		$env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

		/**
		 * Application paths
		 *
		 * This derives two paths: SCRIPT_NAME and PATH_INFO. The SCRIPT_NAME
		 * is the real, physical path to the application, be it in the root
		 * directory or a subdirectory of the public document root. The PATH_INFO is the
		 * virtual path to the requested resource within the application context.
		 *
		 * With htaccess, the SCRIPT_NAME will be an absolute path (without file name);
		 * if not using htaccess, it will also include the file name. If it is "/",
		 * it is set to an empty string (since it cannot have a trailing slash).
		 *
		 * The PATH_INFO will be an absolute path with a leading slash; this will be
		 * used for application routing.
		 */
		if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
			$env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
		} else {
			$env['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); //With URL rewrite
		}
		$env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
		if (strpos($env['PATH_INFO'], '?') !== false) {
			$env['PATH_INFO'] = substr_replace($env['PATH_INFO'], '', strpos($env['PATH_INFO'], '?')); //query string is not removed automatically
		}
		$env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
		$env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/');

		$env['tikori.path_info'] = $env['PATH_INFO'];

		if (Core::app()->cfg()->url->pathInsteadGet and !empty($_GET['d'])) {
			$env['PATH_INFO'] = '/' . $_GET['d'];
			foreach (array_slice($_GET, 1) as $key => $val) {
				$env['PATH_INFO'] .= '/' . $key . '/' . $val;
			}
		}

		//The portion of the request URI following the '?'
		$env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

		//Name of server host that is running the script
		$env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

		//Number of server port that is running the script
		$env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

		//HTTP request headers
		$specialHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE');
		foreach ($_SERVER as $key => $value) {
			$value = is_string($value) ? trim($value) : $value;
			if (strpos($key, 'HTTP_') === 0) {
				$env[substr($key, 5)] = $value;
			} elseif (strpos($key, 'X_') === 0 || in_array($key, $specialHeaders)) {
				$env[$key] = $value;
			}
		}

		//Is the application running under HTTPS or HTTP protocol?
		$env['tikori.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

		//Input stream (readable one time only; not available for mutipart/form-data requests)
//		$rawInput = @file_get_contents('php://input');
//		if (!$rawInput) {
//			$rawInput = '';
//		}
//		$env['slim.input'] = $rawInput;
		//Error stream
//		$env['slim.errors'] = fopen('php://stderr', 'w');
//		$env['tikori.route'] = Route::process_uri($env['PATH_INFO']);
//		if (empty($env['tikori.route']['params'])) {
//			throw new Exception('404');
//		}
		preg_match('#(.*)/(.*?)\.php#i', $env['SCRIPT_NAME'], $match);
		$env['tikori.root_path'] = (count($match) == 3) ? $env['HOST'] . $match[1] : $env['HOST'];
		$env['tikori.base_url'] = $env['tikori.url_scheme'] . '://' . $env['tikori.root_path'] . '/';

		$this->env = $env;
//		Core::app()->cfg('env', $env);
		Core::app()->cfg()->env = $env;
	}

	public function getRouterPath() {
		return (empty($this->env['PATH_INFO'])) ? '' : $this->env['PATH_INFO'];
	}

	public function getPost($val) {
		return (!empty($_POST[$val])) ? $_POST[$val] : null;
	}

}
