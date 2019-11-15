<?php

/**
 * Class TikoriCron
 * @see TikoriConsole
 */
abstract class TikoriCron
{
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
            return array_key_exists($param, $params);
        }

        foreach ($param as $_param) {
            if (array_key_exists($_param, $params)) {
                return true;
            }
        }

        return false;
    }

    protected function _paramGet($param, $params, $default = null)
    {
        if ($this->_paramIsSet($param, $params)) {
            if (!is_array($param)) {
                return $params[$param];
            }

            foreach ($param as $_param) {
                if (array_key_exists($_param, $params)) {
                    return $params[$_param];
                }
            }
        }

        return $default;
    }
}
