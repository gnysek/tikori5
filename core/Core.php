<?php

defined('TIKORI_STARTED') or define('TIKORI_STARTED', microtime());
defined('TIKORI_DEBUG') or define('TIKORI_DEBUG', false);
defined('TIKORI_CPATH') or define('TIKORI_CPATH', dirname(__FILE__));

require_once TIKORI_CPATH . '/tikori/Tikori.php';

/**
 * @author Piotr Gnys <gnysek@gnysek.pl>
 * @package core
 * @version 0.0.1
 */
class Core {

	const MODE_DEBUG = -1;
	const MODE_DEV = 0;
	const MODE_PROD = 1;
	const VERSION = '1.0.0&alpha;';

	/**
	 * @var Core Main app class 
	 */
	private static $_app = null;

	/**
	 *
	 * @var array Registered things
	 */
	private static $_registry = array();

	/**
	 * Runs an application
	 * @param type $path
	 * @param type $config
	 */
	public static function run($path = '', $config = 'default') {
		if (self::$_app === null) {
			return $core = new Tikori($path, $config);
		}
		self::asssignApp();
	}

	/**
	 * Returns current app
	 * @return Tikori
	 */
	public static function app() {
		return self::$_app;
	}

	/**
	 * Assigns application object
	 * @param Tikori $app
	 * @throws Exception
	 */
	public static function asssignApp($app = null) {
		if (self::$_app === null and $app instanceof Tikori) {
			self::$_app = $app;
		} else {
			throw new Exception('Tikori5 cannot be run more than once!');
		}
	}

	/**
	 * Registers values under given name
	 * @param string $name Name of registry
	 * @param mixed $value Value
	 * @param boolean $overwrite Overwrite when exists or not?
	 * @return boolean Whether succeded or not
	 */
	public static function register($name, $value = null, $overwrite = false) {
		if (array_key_exists($name, self::$_registry) and $overwrite === false) {
			return false;
		}
		self::$_registry[$name] = $value;
		return true;
	}

	/**
	 * Gets value from registry
	 * @param string $name If null, will return everything, else will return value under key or $default
	 * @param mixed $default Returns when $name don't exists in registry
	 * @return mixed Return all data when $name is null, registry value if found, or $default when not found
	 */
	public function registry($name = null, $default = null) {
		if ($name === null) {
			return self::$_registry;
		}
		if (array_key_exists($name, self::$_registry)) {
			return self::$_registry[$name];
		}
		return $default;
	}

	/**
	 * Autoloader method
	 * @param type $class Class name
	 * @return boolean
	 * @throws Exception
	 */
	public static function autoload($class, $throw = true) {
		$search = str_replace('_', '/', $class);

		preg_match('#(.*)/(.*)#i', $search, $match);
		if (!empty($match)) {
			$search = strtolower($match[1]) . '/' . $match[2];
		} else {
			$search = ucfirst($class);
		}

		$search = '/' . $search . '.php';

		foreach (Core::app()->autoloadPaths as $dir) {
			$filename = $dir . $search;
			if (file_exists($filename)) {
				if (class_exists('Log')) {
					Log::addLog('Loading <tt>' . $class . '</tt> from <tt>' . $filename . '<tt>');
				}
				require $filename;
				return true;
			}
		}

		if ($throw) {
			throw new Exception("Cannot autoload class " . $class . ' [' . $search . ']');
		}
		return false;
	}

	/**
	 * Gets time from app start to now
	 * @var int $decimalPart how many digists in decimal part you want
	 * @var boolean $returnLessWhenZero should it return &lt; 0.0001 when time == 0
	 * @return string
	 */
	public static function genTimeNow($decimalPart = 4, $returnLessWhenZero = true) {
		$decimalPart = max(1, $decimalPart);

		$arr = explode(' ', TIKORI_STARTED);
		$_time1 = $arr[1] + $arr[0];
		$arr = explode(' ', microtime());
		$_time2 = $arr[1] + $arr[0];

		$_time = round($_time2 - $_time1, $decimalPart);

		$_time = ($_time == 0 and $returnLessWhenZero) ? '&lt; 0.' . str_repeat('0', $decimalPart - 1) . '1' : sprintf('%.' . $decimalPart . 'f', $_time);

		return $_time;
	}

	public static function poweredBy() {
		return 'Powered by <a href="http://tikori5.gnysek.pl/" target="_blank">Tikori5</a> v' . self::VERSION;
	}

}
