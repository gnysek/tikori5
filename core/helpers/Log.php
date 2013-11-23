<?php

class Log
{

    public static function log($data, $file = 'system.log')
    {
        if (!file_exists(TIKORI_ROOT . '/log')) {
            mkdir(TIKORI_ROOT . '/log', true);
        }

        if (!file_exists(TIKORI_ROOT . '/log/' . $file)) {
            file_put_contents(TIKORI_ROOT . '/log/' . $file, '');
        }

        $f = fopen(TIKORI_ROOT . '/log/' . $file, 'a+');
        fwrite($f, str_repeat('-', 80) . PHP_EOL);
        fwrite($f, date('d.m.Y H:i:s') . ': ' . PHP_EOL . var_export($data, true) . PHP_EOL);
        fclose($f);
    }
}
