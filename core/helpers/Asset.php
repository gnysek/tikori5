<?php

class Asset
{

    public $cssPlaceholder = '<link rel="stylesheet" type="text/css" href="%s"/>';
    public $jsPlaceholder = '<script type="text/javascript" src="%s"></script>';

    public function cssAsset($relativeFilePath)
    {
        $relativeFilePath = trim($relativeFilePath, '/');

        if (stripos('http://', $relativeFilePath) === 0) {
            return sprintf($this->cssPlaceholder, $relativeFilePath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf($this->cssPlaceholder, Core::app()->baseUrl() . $relativeFilePath);
        } else if (file_exists(TIKORI_FPATH . '/../' . $relativeFilePath)) {
            return '<style type="text/css">' . file_get_contents(TIKORI_FPATH . '/../' . $relativeFilePath) . '</style>';
        }
    }

    public function jsAsset($relativeFilePath)
    {
        $relativeFilePath = trim($relativeFilePath, '/');

        if (stripos('http://', $relativeFilePath) === 0) {
            return sprintf($this->$jsPlaceholder, $relativeFilePath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf($this->$jsPlaceholder, Core::app()->baseUrl() . $relativeFilePath);
        } else if (file_exists(TIKORI_FPATH . '/../' . $relativeFilePath)) {
            return '<script type="text/javascript">' . file_get_contents(TIKORI_FPATH . '/../' . $relativeFilePath) . '</script>';
        }
    }
}
