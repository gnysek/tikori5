<?php

/**
 * Class Asset
 * Returns links to css/js files according to path (local/outside)
 */
class Asset
{
    protected static $_cssPlaceholder = '<link rel="stylesheet" type="text/css" href="%s"/>';
    protected static $_cssPlaceholderContent = '<style type="text/css">%s</style>';
    protected static $_jsPlaceholder = '<script type="text/javascript" src="%s"></script>';
    protected static $_jsPlaceholderContent = '<script type="text/javascript">%s</script>';

    const TYPE_CSS = 1;
    const TYPE_JS = 2;

    /**
     * @param $relativeFilePath
     * @return string
     */
    public static function cssAsset($relativeFilePath)
    {
        return self::_returnAsset($relativeFilePath, self::TYPE_CSS);
    }

    /**
     * @param $relativeFilePath
     * @return string
     */
    public static function jsAsset($relativeFilePath)
    {
        return self::_returnAsset($relativeFilePath, self::TYPE_JS);
    }

    protected static function _returnAsset($relativeFilePath, $type)
    {
        $relativeFilePath = trim($relativeFilePath, '/');

        if (stripos('http://', $relativeFilePath) === 0) {
            return sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholder : self::$_jsPlaceholder, $relativeFilePath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholder : self::$_jsPlaceholder, Core::app()->baseUrl() . $relativeFilePath);
        } else if (file_exists(TIKORI_FPATH . '/../' . $relativeFilePath)) {
            $filename = TIKORI_FPATH . '/../' . $relativeFilePath;
            return (file_exists($filename)) ? sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholderContent : self::$_jsPlaceholderContent, file_get_contents($filename)) : '';
        }
    }
}
