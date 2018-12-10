<?php

class Log
{

    public static function write($data, $file = 'system.log')
    {
        if (!file_exists(self::logDir())) {
            mkdir(self::logDir(), 0777, true);
        }

        if (!file_exists(self::logDir() . '/' . $file)) {
            file_put_contents(self::logDir() . '/' . $file, '');
        }

        $f = fopen(self::logDir() . '/' . $file, 'a+');
        fwrite($f, str_repeat('-', 80) . PHP_EOL);
        fwrite($f, date('d.m.Y H:i:s') . ': ' . PHP_EOL . var_export($data, true) . PHP_EOL);
        fclose($f);
    }

    public static function logDir()
    {
        return TIKORI_ROOT . '/log';
    }
}
