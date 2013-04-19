<?php

defined('TIKORI_STARTED') or define('TIKORI_STARTED', microtime());
defined('TIKORI_DEBUG') or define('TIKORI_DEBUG', false);
defined('TIKORI_FPATH') or define('TIKORI_FPATH', dirname(__FILE__));

require_once TIKORI_FPATH . '/tikori/Tikori.php';

/**
 * @author  Piotr Gnys <gnysek@gnysek.pl>
 * @package core
 * @version 0.0.1
 */
class Core
{

    const MODE_DEBUG = -1;
    const MODE_DEV = 0;
    const MODE_PROD = 1;
    const VERSION = '5.0.0&alpha;';

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
     *
     * @param string $path
     * @param string $config
     *
     * @return null
     */
    public static function run($path = '', $config = 'default')
    {
        defined('TIKORI_ROOT') or define('TIKORI_ROOT', $path);
        self::createTikoriApplication($config);
    }

    public static function createTikoriApplication($config = null)
    {
        return self::createApplication('Tikori', $config);
    }

    /**
     * @param $class
     * @param $config
     *
     * @return Tikori
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
        if (self::$_app === null) {
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
     * @param string  $name      Name of registry
     * @param mixed   $value     Value
     * @param boolean $overwrite Overwrite when exists or not?
     *
     * @return boolean Whether succeded or not
     */
    public static function register($name, $value = null, $overwrite = false)
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
     * @param string $name    If null, will return everything, else will return value under key or $default
     * @param mixed  $default Returns when $name don't exists in registry
     *
     * @return mixed Return all data when $name is null, registry value if found, or $default when not found
     */
    public function registry($name = null, $default = null)
    {
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
     *
     * @param string $class Class name
     * @param bool   $throw Should it throw or not
     *
     * @return boolean
     * @throws Exception
     */
    public static function autoload($class, $throw = true)
    {
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
                if (class_exists('Profiler')) {
                    Profiler::addLog(
                        '<div style="padding-left: 20px;"><i>Loading <code>' . $class . '</code> from <tt>' . $filename
                            . '<tt></i></div>'
                    );
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
     *
     * @var int     $decimalPart        how many digists in decimal part you want
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

    public static function event($eventName, $data = null)
    {
        Core::app()->observer->fireEvent($eventName, $data);
    }

    public static function shutdown_handler()
    {
        if ($error = error_get_last() AND in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))) {
            ob_get_level() AND ob_clean();
            ob_end_clean();

            Error::exch(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

            exit(1); // prevent infinity-loop
        }
    }

    private static $_tiCoreClasses
        = array(
            'Tikori' => 'tikori/Tikori.php',
        );

}

spl_autoload_register(array('Core', 'autoload'));
