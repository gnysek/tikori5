<?php

class ConsoleRequest extends Request
{
    public function __construct()
    {
        $data = [
            'REQUEST_URI' => '/',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 0,
        ];

        foreach ($data as $k => $v) {
            $_SERVER[$k] = $v;
        }

        parent::__construct();
    }

    protected function getProxyIpAddress()
    {
        return '127.0.0.1';
    }

    public function isGet()
    {
        return true;
    }

    public function isPost()
    {
        return false;
    }

    public function isDelete()
    {
        return false;
    }

    public function isPut()
    {
        return false;
    }

    public static function isFlashRequest()
    {
        return false;
    }

    public function isAjax()
    {
        return false;
    }

    public function isSecure()
    {
        return true;
    }

    public function isCli()
    {
        return true;
    }

    public function isHardRefresh()
    {
        return false;
    }
}
