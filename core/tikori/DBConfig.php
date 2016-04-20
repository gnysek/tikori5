<?php

class DBConfig extends TModule
{

    const CONFIG_FIELD = 'config-allow';

    public function __construct()
    {
        if (Core::app()->db) {
            // preinsall
            if (!Core::app()->db->getSchema()->hasTable('config')) {
                Core::app()->db->update(
                    "CREATE TABLE IF NOT EXISTS `config` (
                      `name` VARCHAR(255) NOT NULL,
                      `scope` VARCHAR(32) NULL DEFAULT NULL,
                      `value` VARCHAR(255) NOT NULL,
                      PRIMARY KEY (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    "
                );
            }

            //
            $_configResult = Core::app()->db->query("SELECT * FROM config ORDER BY name");

            $_configs = Core::app()->cfg(self::CONFIG_FIELD, array());

            foreach ($_configResult as $v) {
                if (in_array($v->name, $_configs)) {
                    Core::app()->cfg()->set($v->name, $v->value, true);
                }
            }
        }
    }
}
