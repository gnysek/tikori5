<?php

/**
 * @const TIKORI_STARTED
 * @const TIKORI_DEBUG
 * @const TIKORI_FPATH
 * @const TIKORI_ROOT
 */

defined('TIKORI_STARTED') or define('TIKORI_STARTED', microtime());
defined('TIKORI_DEBUG') or define('TIKORI_DEBUG', false);
defined('TIKORI_FPATH') or define('TIKORI_FPATH', dirname(__FILE__));

/**
 * @author  Piotr Gnys <gnysek@gnysek.pl>
 * @package core
 * @version 0.0.1
 */
class Core
{

    const MODE_DEBUG = -1;
    const MODE_PROD = 0;
    const MODE_DEV = 1;
    const VERSION = '5.0.0&alpha;';

    /**
     * @var Core Main app class
     */
    private static $_app = NULL;

    /**
     *
     * @var array Registered things
     */
    private static $_registry = array();

    private static $_isconsole = true;

    /**
     * Runs an application
     *
     * @param string $path
     * @param string $config
     *
     * @return null
     */
    public static function run($path = '', $config = 'default')
    {
        defined('TIKORI_ROOT') or define('TIKORI_ROOT', $path);
        chdir(TIKORI_ROOT);

        spl_autoload_register(array('Core', 'autoload'));

        if (self::_isCli()) {
            self::createApplication('TikoriConsole', $config);
        } else {
            self::createApplication('Tikori', $config);
            self::$_isconsole = false;
        }
    }

    public static function isConsoleApplication() {
        return self::$_isconsole;
    }

    protected static function _isCli()
    {
        if (defined('STDIN') || php_sapi_name() === 'cli') {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            return true;
        }

        self::$_isconsole = false;
        return false;
    }

    /**
     * @param $class
     * @param $config
     *
     * @return Tikori|TikoriConsole
     */
    public static function createApplication($class, $config)
    {
        return new $class($config);
    }

    /**
     * Assigns application object
     *
     * @param Tikori $app
     *
     * @throws Exception
     */
    public static function setApplication($app)
    {
        if (self::$_app === NULL) {
            self::$_app = $app;
        } else {
            throw new Exception('Tikori application can be created only once!');
        }
    }

    /**
     * Returns current app
     *
     * @return Tikori
     */
    public static function app()
    {
        return self::$_app;
    }

    /**
     * Registers values under given name
     *
     * @param string $name Name of registry
     * @param mixed $value Value
     * @param boolean $overwrite Overwrite when exists or not?
     *
     * @return boolean Whether succeded or not
     */
    public static function register($name, $value = NULL, $overwrite = false)
    {
        if (array_key_exists($name, self::$_registry) and $overwrite === false) {
            return false;
        }
        self::$_registry[$name] = $value;
        return true;
    }

    /**
     * Gets value from registry
     *
     * @param string $name If null, will return everything, else will return value under key or $default
     * @param mixed $default Returns when $name don't exists in registry
     *
     * @return mixed Return all data when $name is null, registry value if found, or $default when not found
     */
    public function registry($name = NULL, $default = NULL)
    {
        if ($name === NULL) {
            return self::$_registry;
        }
        if (array_key_exists($name, self::$_registry)) {
            return self::$_registry[$name];
        }
        return $default;
    }

    private $_modules = array();

    public static function autoloadByDir($dir = NULL, $class)
    {
        $dirs = array();
        if ($dir == NULL) {
            $dirs = Core::app()->autoloadPaths;
        } else {

        }
    }

    public static $namespacesEnabled = false;

    /**
     * Autoloader method
     *
     * @param string $class Class name
     * @param bool $throw Should it throw or not
     *
     * @return boolean
     * @throws Exception
     */
    public static function autoload($class, $throw = true)
    {
        // TODO: remove self::$namespacesEnabled so if namespace, then path will be taken from it instead of autloadPaths
        if (class_exists($class) and self::$namespacesEnabled == false) {
            return true;
        }

        $namespace = '';
        $class = ltrim($class, '\\');
//        $parts = explode('\\', $class);
//        $class = end($parts);
//        $search = implode('/', $parts);

        if ($ns_pos = strripos($class, '\\')) {
            $namespace = substr($class, 0, $ns_pos) . '\\';
            $class = substr($class, $ns_pos + 1);
        }

        $search = strtolower(str_replace('_', '/', (($namespace and self::$namespacesEnabled) ? ($namespace . '/') : '') . $class));

        preg_match('#(.*)/(.*)#i', $search, $match);
        if (!empty($match) /*and self::$namespacesEnabled*/) {
            $search = strtolower($match[1]) . '/' . $match[2];
        } else {
            $search = ucfirst($class);
        }

        $search = DIRECTORY_SEPARATOR . $search . '.php';

        $filenames = array();

        $pathes = (Core::app()) ? Core::app()->autoloadPaths : array(
            TIKORI_FPATH . DIRECTORY_SEPARATOR,
            TIKORI_FPATH . DIRECTORY_SEPARATOR . 'tikori' . DIRECTORY_SEPARATOR,
        );

        foreach ($pathes as $dir) {
            $filename = $dir . $search;
            $filenames[] = $filename;
            if (file_exists($filename)) {
                if (class_exists('Profiler')) {
                    Profiler::addLog(
                        sprintf('<div style="padding-left: 20px;"><i>Loading <code>%s%s</code> from <kbd>%s<kbd></i></div>', $namespace, $class, $filename)
                    );
                }
                require_once $filename;
                if (!class_exists($namespace.$class) && $throw) {
                    throw new Exception(sprintf('Class %s not found inside autoloaded file [%s]', $namespace.$class, $filename . $search));
                }
                #class_alias($class, '\Tikori\\' . $class);
                return true;
            }
        }

        if ($throw) {
            throw new Exception(sprintf('Cannot autoload class %s [namespace: %s] [search: %s] [filenames: %s]', $class, $namespace, $search, implode(', ' . PHP_EOL, $filenames)));
        }
        return false;
    }

    /**
     * Gets time from app start to now
     *
     * @var int $decimalPart how many digists in decimal part you want
     * @var boolean $returnLessWhenZero should it return &lt; 0.0001 when time == 0
     * @return string
     */
    public static function genTimeNow($decimalPart = 4, $returnLessWhenZero = true)
    {
        $decimalPart = max(1, $decimalPart);

        $arr = explode(' ', TIKORI_STARTED);
        $_time1 = $arr[1] + $arr[0];
        $arr = explode(' ', microtime());
        $_time2 = $arr[1] + $arr[0];

        $_time = round($_time2 - $_time1, $decimalPart);

        $_time = ($_time == 0 and $returnLessWhenZero)
            ? '&lt; 0.' . str_repeat('0', $decimalPart - 1) . '1'
            : sprintf(
                '%.' . $decimalPart . 'f', $_time
            );

        return $_time;
    }

    public static function poweredBy()
    {
        return 'Powered by <a href="http://tikori5.gnysek.pl/" target="_blank">Tikori5</a> v' . self::VERSION;
    }

    public static function getFrameworkPath()
    {
        return TIKORI_FPATH;
    }

    public static function event($eventName, $data = NULL)
    {
        Core::app()->observer->fireEvent($eventName, $data);
    }

    public static function component($componentName)
    {
        return Core::app()->component($componentName);
    }

    private static $_tiCoreClasses = array(
        'Tikori' => 'tikori/Tikori.php',
    );

}
