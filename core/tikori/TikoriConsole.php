<?php

class TikoriConsole extends Application
{

    protected $_cmdMode = null;
    protected $_cmdArg = null;
    protected $_lockModeEnabled = false;
    protected $_lockTime = 0;
    protected $_cleaner = false;

    /**
     * @var ConsoleRequest
     */
    public $request = null;

    const CRON_CACHE_FILE = '.__CRONLOCK__';

    const STATUS_PLANNED = 'planned';
    const STATUS_STARTED = 'started';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';
    const STATUS_TIMEOUT = 'timeout';

    const EVENT_STARTS_ALL_JOBS = 'cron_start_all_jobs';
    const EVENT_START_JOB = 'cron_start_job';
    const EVENT_END_JOB = 'cron_end_job';
    const EVENT_ERROR_JOB = 'cron_error_job';
    const EVENT_PASS_TO_JOB = 'cron_pass_t0_job';

    public function run($config)
    {
        set_error_handler(array(TikoriConsole::class, 'errh'), E_ALL ^ E_NOTICE);
        set_exception_handler(array(TikoriConsole::class, 'exch'));
        ini_set('error_display', 1);
        error_reporting(E_ALL | E_STRICT);

        $this->request = new ConsoleRequest();

        $cmdArgsFound = [];

        if (defined('STDIN')) {
            global $argv, $argc;
            foreach ($argv as $i => $cmdArg) {
                if (substr($cmdArg, 0, 1) == '-') {

                    $cmdMode = str_replace('--', '', $cmdArg);

                    if ($this->_cmdMode === null) {
                        switch ($cmdMode) {
                            case 'version':
                            case '-v':
                                $this->ascii();
                                echo Core::VERSION . PHP_EOL;
                                exit;
                                break;

                            case 'help':
                            case '-h':
                                echo '--version, -v: Shows Tikori version' . PHP_EOL;
                                echo '--help, -h: Shows this help' . PHP_EOL;
                                echo '--task, -t: Runs only one task' . PHP_EOL;
                                echo '--available, -a: Show available tasks' . PHP_EOL;
                                echo '--force, -f: force even when locks are enabled' . PHP_EOL;
                                exit;
                                break;

                            case 'task':
                            case '-t':
                                if ($argc > $i) {
                                    $this->_cmdArg = isset($argv[$i + 1]) ? trim($argv[$i + 1], '\'"') : null;
                                } else {
                                    echo 'Not provided task name' . PHP_EOL;
                                    exit;
                                }
                                break;

                            case 'available':
                            case '-a':
                                echo 'Available tasks:' . PHP_EOL;

                                $_tasks = Core::app()->cfg('cron', []);
                                $_tasks = array_merge($_tasks, Core::app()->cfg('console-only', []));

                                foreach ($_tasks as $j => $_task) {
                                    /** @var string|TikoriCron $_task */
                                    $help = $_task::help();
                                    echo ($j + 1) . '. ' . ($help ?: $_task) . PHP_EOL;
                                }

                                exit;
                                break;

                            default:
                                echo 'Unknown Mode: ' . $cmdMode . PHP_EOL . 'Run with --help/-h to see possible options.' . PHP_EOL;
                                exit;
                                break;
                        }

                        $this->_cmdMode = $cmdMode;
                    }

                    //break; // don't search further
                    $cmdArgsFound[] = $cmdMode;
                }
            }
        }

        // enable cache, we need that for config
        $this->setComponent('cache', new Cache());
        $this->lang = new Lang();

        //TODO: this should be moved to parent!
        if ($this->cfg('db/type') != '') {
            if ($this->cfg('db/type') == 'mysqli') {
                $db = new DbMySqli();
            } else {
                $db = new DbPDO();
            }
            $this->setComponent('db', $db);

            if ($this->cfg(DBConfig::CONFIG_FIELD, null) !== null) {
                $this->setComponent('dbconfig', new DBConfig());
            }
            unset($db);
        }
        // configure modules
        $modules = $this->cfg('modules');
        if (!empty($modules)) {
            foreach ($this->cfg('modules') as $module => $configPath) {

                if ($this->dbconfig) {
                    $this->dbconfig->renconfigure($module);
                }

                $this->preloadModule($module, $configPath);
            }
        }

        ini_set('memory_limit', '512M');

        foreach ($cmdArgsFound as $caf) {
            if (in_array($caf, ['clean', '-c'])) {
                $this->_cleaner = true;
            }
        }

        if (!$this->_cleaner) {
            $this->ascii();
            echo '---> got ' . ini_get('memory_limit') . PHP_EOL;
            echo '---> Starting ' . date('d.m.Y H:i:s') . PHP_EOL;
        }

        $cronTasks = Core::app()->cfg('cron');
        if (is_array($cronTasks)) {

            $disableLock = false;
            foreach ($cmdArgsFound as $caf) {
                $disableLock |= in_array($caf, ['force', '-f']);
            }

            if ($disableLock == false and $cronLock = Core::app()->cfg()->get('cron_lock', null)) {
                if ($cronLock > 0) {
                    $this->_lockModeEnabled = true;
                    $this->_lockTime = $cronLock;
                }
            }

            if ($this->_lockModeEnabled) {
                echo '---> [ LOCK MODE ENABLED! ]' . PHP_EOL;
                if (Core::app()->cache->isFresh(self::CRON_CACHE_FILE, $this->_lockTime)) {
                    echo 'Cron task are locked to be not executed more often than ' . $this->_lockTime . ' seconds, if any already runs/crashed. Skipping current run.' . PHP_EOL;
                    echo 'Next run in ' . ($this->_lockTime - (time() - Core::app()->cache->lastMtime(self::CRON_CACHE_FILE))) . ' seconds.' . PHP_EOL;
                    exit;
                }

                Core::app()->cache->saveCache(self::CRON_CACHE_FILE, time());
            } else {
                Core::app()->cache->deleteCache(self::CRON_CACHE_FILE);
            }

            if (in_array($this->_cmdMode, ['task', '-t'])) {
                // add also those that are "console-only" but not automatically ran by cron
                $cronTasks = array_merge($cronTasks, Core::app()->cfg('console-only', []));

                if (!in_array($this->_cmdArg, $cronTasks)) {
                    echo 'Requested task ' . $this->_cmdArg . ' not found (may be inactive). Should be one of: ' . implode(', ', $cronTasks) . PHP_EOL;
                    return;
                }
            }

            $tasksToExecute = [];
            foreach ($cronTasks as $cronTask) {
                if (in_array($this->_cmdMode, ['task', '-t'])) {
                    if ($this->_cmdArg != $cronTask) {
                        continue;
                    }
                }

                $tasksToExecute[] = $cronTask;
            }

            Core::app()->observer->fireEvent(self::EVENT_STARTS_ALL_JOBS, ['tasks' => $tasksToExecute]);

            // perform
            foreach ($tasksToExecute as $cronTaskId => $cronTask) {
                /* $cronTask TikoriCron */

                Core::app()->observer->fireEvent(self::EVENT_START_JOB, ['taskId' => $cronTaskId]);

                try {
                    $_t = Core::genTimeNow();
                    /** @var TikoriCron $task */
                    $task = new $cronTask;
                    echo '---> Running [' . $cronTask . ']' . PHP_EOL;

                    $params = $this->_prepareTaskParams($task);

                    Core::app()->observer->fireEvent(self::EVENT_PASS_TO_JOB, ['taskId' => $cronTaskId, 'task' => $task]);
                    $task->run($params);
                    echo PHP_EOL . '---> Task [' . $cronTask . '] done in ' . round(Core::genTimeNow() - $_t, 5) . 's !' . PHP_EOL;

                    Core::app()->observer->fireEvent(self::EVENT_END_JOB, ['taskId' => $cronTaskId, 'startTime' => $_t]);
                } catch (Exception $e) {
                    echo 'Cron task [' . $cronTask . '] encountered an error: ' . $e->getMessage() . PHP_EOL;

                    Core::app()->observer->fireEvent(self::EVENT_ERROR_JOB,
                        ['taskId' => $cronTaskId, 'status' => 'Exception', 'message' => 'Error: ' . $e->getMessage()]
                    );
                } catch (Throwable $e) {
                    echo 'Cron task [' . $cronTask . '] encountered a [FATAL] error: ' . $e->getMessage() . PHP_EOL;
                    echo str_replace('#', '   #', $e->getTraceAsString()) . PHP_EOL;

                    Core::app()->observer->fireEvent(self::EVENT_ERROR_JOB,
                        ['taskId' => $cronTaskId, 'status' => 'Exception', 'message' => 'Error: ' . $e->getMessage()]
                    );
                }
            }
        } else {
            echo ':( No cron tasks defined' . PHP_EOL;
        }
        echo PHP_EOL . '--> Finished in ' . Core::genTimeNow(4) . ' s.';
        echo PHP_EOL . '--> Memory usage ' . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';
        echo PHP_EOL;

        Core::app()->cache->deleteCache(self::CRON_CACHE_FILE);
    }

    /**
     * @param $task
     * @return array
     */
    protected function _prepareTaskParams($task)
    {
        /** @var TikoriCron $task */
        $allowedArgs = [];
        $_allowedArgs = $task->allowedParams();
        $fillAlso = [];
        foreach ($_allowedArgs as $_a) {
            if (is_array($_a)) {
                // array of options, link them with same param

                $fixedNames = [];
                foreach ($_a as $_alternatives) {
                    $fixedNames[] = str_replace('--', '', $_alternatives);
                }

                foreach ($fixedNames as $_alternatives) {
                    $allowedArgs[] = $_alternatives;
                    $_f = $fixedNames;
                    unset($_f[array_search($_alternatives, $_f)]);
                    if (count($_f)) {
                        $fillAlso[$_alternatives] = $_f;
                    }
                }

                unset($fixedNames);
            } else {
                $allowedArgs[] = str_replace('--', '', $_a);
            }
        }

        // fill

        $params = [];

        global $argv, $argc;

        for ($i = 1; $i < count($argv); $i++) {
            $cmdArg = $argv[$i];
            if (substr($cmdArg, 0, 1) == '-') {
                $cmdMode = str_replace('--', '', $cmdArg);

                if (in_array($cmdMode, $allowedArgs)) {
                    if (count($argv) > $i + 1) {
                        $params[$cmdMode] = $argv[$i + 1];

                        if (array_key_exists($cmdMode, $fillAlso)) {
                            foreach ($fillAlso[$cmdMode] as $paramName) {
                                $params[$paramName] = $argv[$i + 1];
                            }
                        }

                        $i++;
                    }
                }
            }
        }

        return $params;
    }

    /**
     * Prints LOGO
     */
    protected function ascii()
    {
        ob_start();
        echo <<<ASCII

$$$$$$$$\ $$\ $$\                           $$\       $$$$$$$\  
\__$$  __|\__|$$ |                          \__|      $$  ____| 
   $$ |   $$\ $$ |  $$\  $$$$$$\   $$$$$$\  $$\       $$ |      
   $$ |   $$ |$$ | $$  |$$  __$$\ $$  __$$\ $$ |      $$$$$$$\  
   $$ |   $$ |$$$$$$  / $$ /  $$ |$$ |  \__|$$ |      \_____$$\ 
   $$ |   $$ |$$  _$$<  $$ |  $$ |$$ |      $$ |      $$\   $$ |
   $$ |   $$ |$$ | \\$$\ \\$$$$$$  |$$ |      $$ |      \\$$$$$$  |
   \__|   \__|\__|  \__| \______/ \__|      \__|       \______/ 


ASCII;
        echo PHP_EOL;
        $art = ob_get_clean();

        $i = rand(1, 3);
        $char = ['░', '▒', '▓'];
        $art = str_replace('$', $char[$i - 1], $art);

        echo $art;
    }

    /**
     * show a status bar in the console
     *
     * @param int $done how many items are completed
     * @param int $total how many items are to be done total
     * @param int $size optional size of the status bar
     * @param string $prepend
     * @return  void
     */
    public static function progress($done, $total, $size = 30, $prepend = '')
    {
        static $start_time;
        static $longest_line = 0;

        // if we go over our bound, just ignore it
        if ($done > $total) return;

        $total = max($total, 1);

        if (empty($start_time) or $done == 0) {
            $start_time = time();
            $longest_line = 0;
        }
        $now = time();

        $perc = (double)($done / $total);

        $bar = floor($perc * $size);

        $status_bar = "\r[";
        $status_bar .= str_repeat("=", $bar);
        if ($bar < $size) {
            $status_bar .= ">";
            $status_bar .= str_repeat(" ", $size - $bar);
        } else {
            $status_bar .= "=";
        }

        $disp = number_format($perc * 100, 0);

        $status_bar .= "] $disp%  $done/$total";

        $rate = $done == 0 ? 0 : (($now - $start_time) / $done);
        $left = $total - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $start_time;

        $status_bar .= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";

        if (!empty($prepend)) {
            $status_bar .= ' [' . $prepend . ']';
        }

        echo "$status_bar  ";
        if (strlen($status_bar) < $longest_line) {
            echo str_repeat(' ', $longest_line - strlen($status_bar));
        }

        $longest_line = strlen($status_bar);

        flush();

        // when done, send a newline
        if ($done == $total) {
            echo PHP_EOL;
        }

    }

    public static function errh($errno, $errstr, $errfile, $errline, $errcontext)
    {
        echo $errno . ':' . $errstr . PHP_EOL . $errfile . ':' . $errline . PHP_EOL . PHP_EOL;
    }

    /**
     * @param Exception $e
     */
    public static function exch($e)
    {
        echo $e->getCode() . ':' . $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine() . PHP_EOL . PHP_EOL;
    }
}
