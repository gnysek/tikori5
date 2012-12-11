<?php

class Response {

	protected $_status;
	protected $_header;
	protected $_body;
	protected $_length;

	/**
	 * @var array HTTP response codes and messages
	 */
	public static $messages = array(
		//Informational 1xx
		100 => '100 Continue',
		101 => '101 Switching Protocols',
		//Successful 2xx
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',
		//Redirection 3xx
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		306 => '306 (Unused)',
		307 => '307 Temporary Redirect',
		//Client Error 4xx
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		422 => '422 Unprocessable Entity',
		423 => '423 Locked',
		//Server Error 5xx
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported'
	);

	public function __construct($body = '', $status = 200, $header = array()) {
		$this->_status = $status;
//		$headers = array();
//		foreach ($header as $key => $value) {
//			$headers[$key] = $value;
//		}
		$this->_header = array_merge(array('Content-Type' => 'text/html', 'X-Powered-By' => 'Tikori5'), $header);
		$this->write($body, true);
	}

	/**
	 * Get and set status
	 * @param	int|null $status
	 * @return	int
	 */
	public function status($status = null) {
		if (!is_null($status)) {
			$this->_status = (int) $status;
		}
		return $this->_status;
	}

	/**
	 * Get and set header
	 * @param   string          $name   Header name
	 * @param   string|null     $value  Header value
	 * @return  string                  Header value
	 */
//    public function header( $name, $value = null ) {
//        if ( !is_null($value) ) {
//            $this[$name] = $value;
//        }
//        return $this[$name];
//    }

	/**
	 * Get and set body
	 * @param   string|null  $body   Content of HTTP response body
	 * @return  string
	 */
	public function body($body = null) {
		if (!is_null($body)) {
			$this->write($body, true);
		}
		return $this->_body;
	}

	/**
	 * Append HTTP response body
	 * @param   string  $body       Content to append to the current HTTP response body
	 * @param   bool    $replace    Overwrite existing response body?
	 * @return  string              The updated HTTP response body
	 */
	public function write($body, $replace = false) {
		if ($replace) {
			$this->_body = $body;
		} else {
			$this->_body .= (string) $body;
		}
		$this->_length = strlen($this->_body);
		return $this->_body;
	}

	/**
	 * Get and set length
	 * @param   int|null     $length
	 * @return  int
	 */
	public function length($length = null) {
		if (!is_null($length)) {
			$this->_length = (int) $length;
		}
		return $this->_length;
	}

	/**
	 * Finalize
	 *
	 * This prepares this response and returns an array
	 * of [status, headers, body]. This array is passed to outer middleware
	 * if available or directly to the Slim run method.
	 *
	 * @return array[int status, array headers, string body]
	 */
	public function finalize() {
		if (in_array($this->_status, array(204, 304))) {
			unset($this['Content-Type'], $this['Content-Length']);
			return array($this->_status, $this->_header, '');
		} else {
			return array($this->_status, $this->_header, $this->_body);
		}
	}

	/**
	 * Redirect
	 *
	 * This method prepares this response to return an HTTP Redirect response
	 * to the HTTP client.
	 *
	 * @param   string  $url        The redirect destination
	 * @param   int     $status     The redirect HTTP status code
	 */
	public function redirect($url, $status = 302) {
		$this->status = $status;
		$this['Location'] = $url;
	}

	/**
	 * Get message for HTTP status code
	 * @return string|null
	 */
	public static function getMessageForCode($status) {
		if (isset(self::$messages[$status])) {
			return self::$messages[$status];
		} else {
			return null;
		}
	}

	/** TODO: cookies: set,delete, * */
}
