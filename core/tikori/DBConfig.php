<?php

class DBConfig extends TModule
{

    const CONFIG_FIELD = 'config-allow';

    protected $_loadedButNotAssigned = [];

    public function __construct()
    {
        if (Core::app()->db) {
            // preinsall
            if (!Core::app()->db->getSchema()->hasTable('config')) {
                Core::app()->db->update(
                    "CREATE TABLE IF NOT EXISTS `config` (
                      `name` VARCHAR(255) NOT NULL,
                      `scope` INT( 3 ) NULL DEFAULT  '0',
                      `value` VARCHAR(255) NOT NULL,
                      PRIMARY KEY (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    "
                );
            }

            //
            $_configResult = Core::app()->db->query("SELECT * FROM config ORDER BY name");

            $_configs = Core::app()->cfg(self::CONFIG_FIELD, []);

            foreach ($_configResult as $v) {
                if (in_array($v->name, $_configs)) {
                    Core::app()->cfg()->set($v->name, $v->value, true);
                } else {
                    $this->_loadedButNotAssigned[$v->name] = $v->value;
                }
            }
        }
    }

    /**
     * @param $moduleName
     * @return bool
     */
    public function renconfigure($moduleName)
    {
        foreach ($this->_loadedButNotAssigned as $name => $value) {
            if (preg_match('/^' . $moduleName . '::/i', $name)) {
                Core::app()->cfg()->set(str_replace(strtolower($moduleName) . '::', '', $name), $value, true);
            }
        }

        return true;
    }

    public function saveConfig($path, $value = null, $moduleName = null)
    {
        if ($moduleName !== null) {
            $moduleName = strtolower($moduleName) . '::';
        }

        Core::app()->cfg()->set($path, $value, true);
        Core::app()->db->update("REPLACE INTO config (name, scope, value) VALUES (" . Core::app()->db->protect($moduleName . $path) . ", 0, " . Core::app()->db->protect($value) . ");");
        return;
    }
}
