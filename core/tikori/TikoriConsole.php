<?php

class TikoriConsole extends Application
{

    protected $_cmdMode = null;
    protected $_cmdArg = null;

    public function run($config)
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('error_display', 1);

        if (defined('STDIN')) {
            global $argv, $argc;
            foreach ($argv as $i => $cmdArg) {
                if (substr($cmdArg, 0, 2) == '--') {

                    $cmdMode = str_replace('--', '', $cmdArg);

                    switch ($cmdMode) {
                        case 'v':
                            echo Core::VERSION . PHP_EOL;
                            exit;
                            break;
                        case 'help':
                            echo '--v - Shows Tikori version' . PHP_EOL;
                            echo '--help - Shows this help' . PHP_EOL;
                            echo '--task - Runs only one task' . PHP_EOL;
                            exit;
                            break;
                        case 'task':
                            if ($argc > $i) {
                                $this->_cmdArg = isset($argv[$i + 1]) ? $argv[$i + 1] : null;
                            } else {
                                echo 'Not provided task name' . PHP_EOL;
                                exit;
                            }
                            break;
                        default:
                            echo 'Unkown Mode: ' . $cmdMode;
                            exit;
                            break;
                    }

                    $this->_cmdMode = $cmdMode;

                    break; // don't search further
                }
            }
        }

        // enable cache, we need that for config
        $this->setComponent('cache', new Cache());
        $this->lang = new Lang();

        //TODO: this should be moved to parent!
        if ($this->cfg('db/type') != "") {
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

        $cronTasks = Core::app()->cfg('cron');
        if (is_array($cronTasks)) {

            if ($this->_cmdMode == 'task') {
                if (!in_array($this->_cmdArg, $cronTasks)) {
                    echo 'Requested task ' . $this->_cmdArg . ' not found. Should be one of: ' . implode(', ', $cronTasks) . PHP_EOL;
                    exit;
                }
            }

            foreach ($cronTasks as $cronTask) {
                /* $cronTask TikoriCron */

                if ($this->_cmdMode == 'task') {
                    if ($this->_cmdArg != $cronTask) {
                        continue;
                    }
                }

                try {
                    $_t = Core::genTimeNow();
                    $task = new $cronTask;
                    echo '---> Running [' . $cronTask . ']' . PHP_EOL;
                    $task->run();
                    echo PHP_EOL . '---> Task [' . $cronTask . '] done in ' . (Core::genTimeNow() - $_t) . 's !' . PHP_EOL;
                } catch (Exception $e) {
                    echo 'Cron task [' . $cronTask . '] encountered an error: ' . $e->getMessage() . PHP_EOL;
                } catch (Throwable $e) {
                    echo 'Cron task [' . $cronTask . '] encountered a [FATAL] error: ' . $e->getMessage() . PHP_EOL;
                    echo str_replace('#', '   #', $e->getTraceAsString()) . PHP_EOL;
                }
            }
        } else {
            echo ':( No cron tasks defined' . PHP_EOL;
        }
        echo PHP_EOL . '--> Finished in ' . Core::genTimeNow(4) . 's';
        echo PHP_EOL;
    }
}
