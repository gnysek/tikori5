<?php

class Asset
{

    public static $cssPlaceholder = '<link rel="stylesheet" type="text/css" href="%s"/>';
    public static $jsPlaceholder = '<script type="text/javascript" src="%s"></script>';

    public static function cssAsset($relativeFilePath)
    {
        $relativeFilePath = trim($relativeFilePath, '/');

        if (stripos('http://', $relativeFilePath) === 0) {
            return sprintf(self::cssPlaceholder, $relativeFilePath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf(self::cssPlaceholder, Core::app()->baseUrl() . $relativeFilePath);
        } else if (file_exists(TIKORI_FPATH . '/../' . $relativeFilePath)) {
            return
                '<style type="text/css">' . file_get_contents(TIKORI_FPATH . '/../' . $relativeFilePath) . '</style>';
        }
    }

    public static function jsAsset($relativeFilePath)
    {
        $relativeFilePath = trim($relativeFilePath, '/');

        if (stripos('http://', $relativeFilePath) === 0) {
            return sprintf(self::$jsPlaceholder, $relativeFilePath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf(self::$jsPlaceholder, Core::app()->baseUrl() . $relativeFilePath);
        } else if (file_exists(TIKORI_FPATH . '/../' . $relativeFilePath)) {
            return '<script type="text/javascript">' . file_get_contents(TIKORI_FPATH . '/../' . $relativeFilePath)
            . '</script>';
        }
    }
}
