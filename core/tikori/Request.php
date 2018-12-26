<?php

/**
 * Provides all data about request made by user
 * Cookie, Urls, server info, IP, Request method, headers, etc.
 *
 * @param string $request_method (GET|POST|PUT|DELETE)
 * @param string $script_name
 */

use \Core\Common\DefaultObject as DefaultObject;

class Request extends DefaultObject
{

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const ROUTE_TOKEN = 'p';

    const REQUEST_METHOD = 'request-method';
    const SCRIPT_NAME = 'script-name';
    const PATH_INFO = 'path-info';
    const QUERY_STRING = 'query';
    const SERVER_NAME = 'server';
    const SERVER_PORT = 'port';
    const ACCEPT = 'accept';
    const ACCEPT_LANG = 'accept-language';
    const ACCEPT_CHARSET = 'accept-charset';
    const USER_AGENT = 'user-agent';
    const REMOTE_ADDR = 'ip';
    const ROUTE = 'route';
    const URL_SCHEME = 'protocol-scheme';

    public $env = array();
    private $_scriptUrl = NULL;
    private $_baseUrl = NULL;
    private $_hostInfo = NULL;

    private $_post = array();
    private $_get = array();

    const UNKNOWN_BROWSER = 'Unknown';

    //public $secure // move to method ?




    // Mimetypes
    protected $_mimeTypes = array(
        'txt'   => 'text/plain',
        'html'  => 'text/html',
        'xhtml' => 'application/xhtml+xml',
        'xml'   => 'application/xml',
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'csv'   => 'text/csv',

        // images
        'png'   => 'image/png',
        'jpe'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'bmp'   => 'image/bmp',
        'ico'   => 'image/vnd.microsoft.icon',
        'tiff'  => 'image/tiff',
        'tif'   => 'image/tiff',
        'svg'   => 'image/svg+xml',
        'svgz'  => 'image/svg+xml',

        // archives
        'zip'   => 'application/zip',
        'rar'   => 'application/x-rar-compressed',

        // adobe
        'pdf'   => 'application/pdf'
    );

    public function mock()
    {
        return array(
            self::REQUEST_METHOD => 'GET',
            self::SCRIPT_NAME    => 'index.php',
            self::PATH_INFO      => '',
            self::QUERY_STRING   => '',
            self::SERVER_NAME    => 'localhost',
            self::SERVER_PORT    => 80,
            self::ACCEPT         => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            self::ACCEPT_LANG    => 'en-US,en;q=0.8',
            self::ACCEPT_CHARSET => 'ISO-8859-2,utf-8;q=0.7,*;q=0.3',
            self::USER_AGENT     => 'Tikori Framework',
            self::REMOTE_ADDR    => '127.0.0.1',
            self::URL_SCHEME     => 'http',
            self::ROUTE          => array(),
//			'tikori.input' => ''
        );
    }

    private function getProxyIpAddress() {
        static $forwarded = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        );

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        foreach ($forwarded as $key) {
            if (array_key_exists($key, $_SERVER)) {
                sscanf($_SERVER[$key], '%[^,]', $ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false) {
                    return $ip;
                }
            }
        }

        return '';
    }

    public function isGet()
    {
        return ($_SERVER['REQUEST_METHOD'] === 'GET');
    }

    public function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] === 'POST');
    }

    public function isDelete()
    {
        return ($_SERVER['REQUEST_METHOD'] === 'DELETE');
    }

    public function isPut()
    {
        return ($_SERVER['REQUEST_METHOD'] === 'PUT');
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    public static function isFlashRequest()
    {
        return ('Shockwave Flash' === $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Is this an AJAX request?
     *
     * @return bool
     */
    public function isAjax()
    {
        if ($this->params('isajax')) {
            return true;
        } elseif (getenv('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
            return true;
        } elseif (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { // FastCGI fix
            return true;
        } else {
            return false;
        }
    }

    public function isSecure()
    {
        return ($this->scheme() === 'https');
    }

    /**
     * Is the request from CLI (Command-Line Interface)?
     *
     * @return boolean
     */
    public function isCli()
    {
        return !isset($_SERVER['HTTP_HOST']);
    }

    /**
     * Fetch GET and POST data
     *
     * This method returns a union of GET and POST data as a key-value array, or the value
     * of the array key if requested; if the array key does not exist, NULL is returned.
     *
     * @param  string $key
     *
     * @return array|mixed|null
     */
    public function params($key = NULL)
    {
        $union = array_merge($this->paramGet(), $this->paramPost());
        if ($key) {
            if (isset($union[$key])) {
                return $union[$key];
            } else {
                return NULL;
            }
        } else {
            return $union;
        }
    }

    public function paramGet()
    {
        return (empty($_GET)) ? array() : $_GET;
    }

    public function paramPost()
    {
        return (empty($_POST)) ? array() : $_POST;
    }

    public function __construct()
    {
        // Die magic_quotes, just die...
        if(get_magic_quotes_gpc()) {
            $stripslashes_gpc = function(&$value, $key) {
                $value = stripslashes($value);
            };
            array_walk_recursive($_GET, $stripslashes_gpc);
            array_walk_recursive($_POST, $stripslashes_gpc);
            array_walk_recursive($_COOKIE, $stripslashes_gpc);
            array_walk_recursive($_REQUEST, $stripslashes_gpc);
        }

        $this->mock();

        $env = array();
        //The HTTP request method
        $env[self::REQUEST_METHOD] = $_SERVER['REQUEST_METHOD'];

        //The IP
//		$env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $env[self::REMOTE_ADDR] = $_SERVER['REMOTE_ADDR'];
        $env['proxy-ip'] = $this->getProxyIpAddress();

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
            $env['script-name'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
        } else {
            $env['script-name'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); //With URL rewrite
        }
        $env['path-info'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['script-name']));
        if (strpos($env['path-info'], '?') !== false) {
            $env['path-info'] = substr_replace(
                $env['path-info'], '', strpos($env['path-info'], '?')
            ); //query string is not removed automatically
        }
        $env['script-name'] = rtrim($env['script-name'], '/');
        $env['path-info'] = '/' . ltrim($env['path-info'], '/');

        $env['tikori.path-info'] = $env['path-info'];

//		var_dump(Core::app()->cfg('url/pathInsteadGet'));

        if (Core::app()->cfg('url/pathInsteadGet') === true and !empty($_GET[self::ROUTE_TOKEN])) {
            $env['path-info'] = '/' . $_GET[self::ROUTE_TOKEN];
            foreach (array_slice($_GET, 1) as $key => $val) {
                $env['path-info'] .= '/' . $key . '/' . $val;
            }
        }

//		var_dump($env['path-info']);
        //The portion of the request URI following the '?'
        $env['query-string'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        //Name of server host that is running the script
        $env['server-name'] = $_SERVER['SERVER_NAME'];

        //Number of server port that is running the script
        $env['server-port'] = $_SERVER['SERVER_PORT'];

        //HTTP request headers
        $specialHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE');
        foreach ($_SERVER as $key => $value) {
            $value = is_string($value) ? trim($value) : $value;
            if (strpos($key, 'HTTP_') === 0) {
                $key = substr($key, 5);
            } elseif (strpos($key, 'X_') === 0 || in_array($key, $specialHeaders)) {
                //
            } else {
                continue;
            }

            $key = strtolower(str_replace('_','-', $key));

            $env[$key] = $value;
            #$env['x-'.$key] = $value;
        }

        //Is the application running under HTTPS or HTTP protocol?
        $env['tikori.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

        //Input stream (readable one time only; not available for mutipart/form-data requests)
		$rawInput = @file_get_contents('php://input');
		if (!$rawInput) {
			$rawInput = '';
		}
		$env['raw-input'] = $rawInput;
        $env['raw-type'] = getenv('CONTENT_TYPE') ?: '';
        $env['raw-length'] = getenv('CONTENT_LENGTH') ?: 0;
        $env['is-secure'] = getenv('HTTPS') && getenv('HTTPS') != 'off';
        $env['tikori.base-dir'] = str_replace(array('\\',' '), array('/','%20'), dirname($_SERVER['SCRIPT_NAME']));

        preg_match('#(.*)/(.*?)\.php#i', $env['script-name'], $match);
        $env['tikori.root_path'] = (count($match) == 3) ? $env['host'] . $match[1] : $env['host'];
        if (Core::app()->cfg('url/addScriptName') === true) {
            $path = ((count($match) != 3)) ? $env['script-name'] : dirname($env['script-name']);
            $env['tikori.root_path'] = $env['host'] . $path;
        }

        $env['tikori.base_url'] = $env['tikori.url_scheme'] . '://' . $env['tikori.root_path'] . '/';

        $parsedUrl = parse_url($env['tikori.base_url']);

        $host = explode('.', $parsedUrl['host']);

        $env['tikori.domain'] = (count($host) >= 2) ? $host[count($host)-2] . '.' . $host[count($host)-1] : $host[0];
        $env['tikori.subdomains'] = array_slice($host, 0, count($host) - 2);

        if (!empty($subdomains)) {
            $env['tikori.base_url'] = str_replace(implode('.', $subdomains) . '.', '', $env['tikori.base_url']);
        }

        if (!empty($_GET)) {
            foreach ($_GET as $k => $v) {
                $this->_get[$k] = trim(preg_replace('#[^a-z0-9/_\-%\s]#i', '', $v));
            }
        }

        if (!empty($_POST)) {
            foreach ($_POST as $k => $v) {
                $this->_post[$k] = (is_array($v)) ? $v : trim(stripslashes($v));
            }
        }

        $env['is-ajax'] = $this->isAjax();
        $env['protocol'] = getenv('SERVER_PROTOCOL') ?: 'HTTP/1.1';

        ksort($env);
        $this->replace($env);
//		Core::app()->cfg('env', $env);
//		Core::app()->cfg()->env = $env;
        return Core::app()->cfg()->set('request', $this->all(), true);
    }

    protected $_hardRefresh = null;

    public function isHardRefresh()
    {
        if ($this->_hardRefresh === null) {
            // request headers
            $headersToCheck = array('If-Modified-Since', 'Pragma', 'Cache-Control');
            $cacheRequest = array();
            foreach ($headersToCheck as $header) {
                $envName = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                if (!empty($_SERVER[$envName])) {
                    $cacheRequest[$header] = $_SERVER[$envName];
                } else {
                    $cacheRequest[$header] = null;
                }
            }

            $this->_hardRefresh = false;
            if ($cacheRequest['Pragma'] == 'no-cache') {
                $this->_hardRefresh = true;
            }
            if ($cacheRequest['Cache-Control'] == 'no-cache') {
                $this->_hardRefresh = true;
            }
        }

        return $this->_hardRefresh;
    }

    public function getRouterPath()
    {
        //return (empty($this->env['PATH_INFO'])) ? '' : $this->env['PATH_INFO'];
        return $this->get('path-info');
    }

    public function getPost($key = null, $default = NULL)
    {
        if (empty($key)) {
            return $this->_post;
        }
        //return (!empty($_POST[$val])) ? $_POST[$val] : null;
        return (array_key_exists($key, $this->_post)) ? $this->_post[$key] : $default;
    }

    public function getParam($key = NULL, $default = NULL)
    {
        if (empty($key)) {
            return array_merge($this->_get, $this->_post);
        }

        return (array_key_exists($key, $this->_get)) ? $this->_get[$key] : $this->getPost($key, $default);
    }

    public function forceParam($key, $value, $isGet = true)
    {
        if ($isGet) {
            $this->_get[$key] = $value;
        } else {
            $this->_post[$key] = $value;
        }
        return true;
    }

    public function clearParams($get = true) {
        if ($get) {
            $this->_get = array();
        } else {
            $this->_post = array();
        }
    }

    public function getBaseUrl($absolute = false)
    {
        if ($this->_baseUrl === NULL) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }

        return $absolute ? ($this->getHostInfo() . rtrim($this->_baseUrl, '/') . '/') : $this->_baseUrl . '/';
    }

    public function getCurrentUrl()
    {
        throw new Exception('TRequest::getCurrentUrl is not yet implemented');
    }

    public function getHostInfo()
    {
        if ($this->_hostInfo === NULL) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_hostInfo = $this->get('tikori.url_scheme') . '://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->_hostInfo = $this->get('tikori.url_scheme') . '://' . $_SERVER['SERVER_NAME'];
            }
        }

        return $this->_hostInfo;
    }

    /**
     * Get Client Ip
     *
     * @param string $default
     *
     * @return string
     */
    public function clientIp($default = '0.0.0.0')
    {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $ips = explode(',', $_SERVER[$key], 1);
            $ip = $ips[0];
            if (false != ip2long($ip) && long2ip(ip2long($ip) === $ip)) {
                return $ips[0];
            }
        }

        return $default;
    }

    public function scheme()
    {
        return $this->get('tikori.url_scheme');
    }

    /**
     * Returns the relative URL of the entry script.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     *
     * @throws Exception
     * @return string the relative URL of the entry script.
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === NULL) {
            $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } else {
                if (basename($_SERVER['PHP_SELF']) === $scriptName) {
                    $this->_scriptUrl = $_SERVER['PHP_SELF'];
                } else {
                    if (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                        $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
                    } else {
                        if (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                            $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
                        } else {
                            if (
                                isset($_SERVER['DOCUMENT_ROOT'])
                                && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0
                            ) {
                                $this->_scriptUrl = str_replace(
                                    '\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME'])
                                );
                            } else {
                                throw new Exception(__CLASS__ . ' is unable to determine the entry script URL.');
                            }
                        }
                    }
                }
            }
        }
        return $this->_scriptUrl;
    }

}
