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
        return self::_returnAsset($relativeFilePath, self::TYPE_CSS) . PHP_EOL;
    }

    public static function mergeCssAssets($listOfCssFiles)
    {

        if (Core::app()->request->isHardRefresh() && Core::app()->getMode() == Core::MODE_DEV) {
            $dir = TIKORI_ROOT . '/media/assets';

            if (file_exists($dir)) {
                foreach (new DirectoryIterator($dir) as $fileInfo) {
                    if (!$fileInfo->isDot() && !$fileInfo->isDir()) {
                        unlink($fileInfo->getPathname());
                    }
                }
            }
        }

        Profiler::addLog('File merging started');
        $externalFiles = array();
        $filesToMerge = array();

        foreach ($listOfCssFiles as $cssFile) {
            if (stripos($cssFile, 'http') === 0) {
                $externalFiles[] = self::cssAsset($cssFile);
            } else {
                $filesToMerge[] = ltrim($cssFile, '/\\');
            }
        }


        if (count($filesToMerge)) {
            $mergedFilename = '';
            $filesToMergeThatExists = array();
            foreach ($filesToMerge as $k => $file) {

                $filesrc = $file;

                if (file_exists(TIKORI_ROOT . '/' . $filesrc)) {
                    $filesrc = TIKORI_ROOT . '/' . $file;
                }

                if (file_exists($filesrc)) {
                    $mergedFilename .= basename($file) . filemtime($filesrc) . '_';
                    $filesToMergeThatExists[] = $filesrc;
                }
            }

            $mergedFilename = md5($mergedFilename) . '.css';

            if (!file_exists(TIKORI_ROOT . '/media/assets')) {
                mkdir(TIKORI_ROOT . '/media/assets', 0777, true);
            }

            if (!file_exists(TIKORI_ROOT . '/media/assets/' . $mergedFilename)) {
                $css = '';
                foreach ($filesToMergeThatExists as $file) {
                    $css .= '/* * ' . basename($file) . ' * */ ';
                    $css .= file_get_contents($file) . "\r\n";
                }

                $css = CssMin::minify($css, array('RemoveComments' => false));
                $css = str_replace(array('/* *', '* */'), array("\r\n/* *", "* */\r\n"), $css);

                file_put_contents(TIKORI_ROOT . '/media/assets/' . $mergedFilename, $css);
            }

            $externalFiles[] = self::cssAsset('/media/assets/' . $mergedFilename);
        }
        Profiler::addLog('File merging ended');

        return $externalFiles;
    }

    static protected $_alreadyJs = array();

    /**
     * @param $relativeFilePath
     * @return string
     */
    public static function jsAsset($relativeFilePath)
    {
        if (!in_array($relativeFilePath, self::$_alreadyJs)) {
            self::$_alreadyJs[] = $relativeFilePath;
            return self::_returnAsset($relativeFilePath, self::TYPE_JS) . PHP_EOL;
        }
        return '';
    }

    protected static function _returnAsset($relativeFilePath, $type)
    {
        $filepath = trim($relativeFilePath, '/');
        if (strpos($relativeFilePath, '?') > 0) {
            $relativeFilePath = substr($relativeFilePath, 0, strpos($relativeFilePath, '?'));
        }
        $filename = TIKORI_FPATH . '/../' . $relativeFilePath;

        if (stripos($relativeFilePath, 'http') === 0) { //http / https
            return sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholder : self::$_jsPlaceholder, $filepath);
        }

        if (file_exists(TIKORI_ROOT . '/' . $relativeFilePath)) {
            return sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholder : self::$_jsPlaceholder, Core::app()->baseUrl() . $filepath);
        } else if (file_exists($filename)) {
            return (file_exists($filename)) ? sprintf(($type == self::TYPE_CSS) ? self::$_cssPlaceholderContent : self::$_jsPlaceholderContent, file_get_contents($filename)) : '';
        }
    }

    public static function purgeAssets()
    {
        foreach (new DirectoryIterator(TIKORI_ROOT . '/media/assets') as $fileInfo) {
            if (!$fileInfo->isDot()) {
                unlink($fileInfo->getPathname());
            }
        }
    }
}
