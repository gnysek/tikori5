<?php

class TikoriConsole extends Application
{

    public function run($config)
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('error_display', 1);

        //TODO: this should be moved to parent!
        if ($this->cfg('db/type') != "") {
            if ($this->cfg('db/type') == 'mysqli') {
                $db = new DbMySqli();
            } else {
                $db = new DbPDO();
            }
            $this->setComponent('db', $db);
        }
        // configure modules
        $modules = $this->cfg('modules');
        if (!empty($modules)) {
            foreach ($this->cfg('modules') as $module => $configPath) {
                $this->preloadModule($module, $configPath);
            }
        }

        $cronTasks = Core::app()->cfg('cron');
        if (is_array($cronTasks)) {
            foreach($cronTasks as $cronTask) {
                /* $cronTask TikoriCron */
                try {
                    $task = new $cronTask;
                    echo '---> Running [' . $cronTask . ']' . PHP_EOL;
                    $task->run();
                    echo PHP_EOL . '---> Task [' . $cronTask . '] done !' . PHP_EOL;
                }
                catch (Exception $e) {
                    echo 'Cron task [' . $cronTask .'] encountered an error: ' . $e->getMessage();
                }

            }
        }
    }
}
