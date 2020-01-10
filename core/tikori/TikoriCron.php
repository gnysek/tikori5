<?php

/**
 * Class TikoriCron
 * @see TikoriConsole
 */
abstract class TikoriCron
{
    /**
     * @var null|CronResult Only if module is installed
     */
    public $statusModel = null;

    abstract public function run($params = []);

    public function allowedParams()
    {
        return [];
    }

    public static function help()
    {
        return false;
    }

    /**
     * @param string|array $param
     * @param array $params
     * @return bool
     */
    protected function _paramIsSet($param, $params)
    {
        if (!is_array($param)) {
            $param = str_replace('--', '', $param);
            return array_key_exists($param, $params);
        }

        foreach ($param as $_param) {
            $_param = str_replace('--', '', $_param);
            if (array_key_exists($_param, $params)) {
                return true;
            }
        }

        return false;
    }

    protected function _paramGet($param, $params, $default = null)
    {
        if ($this->_paramIsSet($param, $params)) {
            $param = str_replace('--', '', $param);
            if (!is_array($param)) {
                return $params[$param];
            }

            foreach ($param as $_param) {
                $_param = str_replace('--', '', $_param);
                if (array_key_exists($_param, $params)) {
                    return $params[$_param];
                }
            }
        }

        return $default;
    }
}
