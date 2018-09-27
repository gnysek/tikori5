<?php

class TCache extends TModule
{

    /**
     * @var int Stores current unix time
     */
    public $cacheTime = 0;
    /**
     * @var string app/cache/ - cache path
     */
    public $cachePath = '';

    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2629743;

    /**
     * Initial settings
     */
    public function __construct()
    {
        $this->cachePath = Core::app()->appDir . '/cache/';
        $this->cacheTime = time();
        if (!file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    /**
     * @param string $newPath new path app/cache/
     */
//    public function cachePath($newPath)
//    {
//        $this->cachePath = Core::app()->appDir . '/cache/' . $newPath;
//    }

    /**
     * Saves to cache
     *
     * @param string $filename
     * @param string $content
     *
     * @return bool
     */
    public function saveCache($filename, $content)
    {
        if (!$this->findCache($filename)) {
            $f = fopen($this->cachePath . $filename, 'w');
            fclose($f);
        }
        file_put_contents($this->cachePath . $filename, $content);
        return true;
    }

    /**
     * Loads cache
     *
     * @param string $filename filename to load
     * @param mixed $defaultContent default content returned
     *
     * @param bool $dontReturnMsg
     * @return bool|string
     */
    public function loadCache($filename, $defaultContent = null, $dontReturnMsg = false)
    {
        if ($this->findCache($filename)) {
            return file_get_contents($this->cachePath . $filename);
        } else {
            return ($defaultContent === null)
                ? (($dontReturnMsg === false) ? '[Cache is empty]' : $defaultContent)
                : $defaultContent;
        }
    }

    /**
     * @param $filename
     * @param $maxage
     * @param null $defaultContent
     * @param bool $dontReturnMsg
     * @return bool|null|string
     */
    public function loadCacheIfFresh($filename, $maxage, $defaultContent = null, $dontReturnMsg = false)
    {
        if (!$this->isFresh($filename, $maxage)) {
            return ($defaultContent === null)
                ? (($dontReturnMsg === false) ? '[Cache is empty]' : $defaultContent)
                : $defaultContent;
        } else {
            return $this->loadCache($filename, $defaultContent, $dontReturnMsg);
        }
    }

    /**
     * Gets last modification time
     *
     * @param string $filename
     * @param bool $check
     * @param int $distance
     *
     * @return mixed|bool
     */
    public function lastMtime($filename, $check = false, $distance = 0)
    {
        $result = 0;

        if ($this->findCache($filename)) {
            $result = filemtime($this->cachePath . $filename);
        }
        // return result when seconds are not set
        if ($check === false) {
            return $result;
        }
        // retusn is it newer or not
        if ($result > ($this->cacheTime - $distance)) {
            return true;
        } else {
            return false;
        }
    }

    public function isFresh($file, $time)
    {
        return $this->lastMtime($file, true, $time);
    }


    /**
     * Checks that cache exists
     *
     * @param string $filename
     *
     * @return bool
     */
    public function findCache($filename)
    {
        return file_exists($this->cachePath . $filename);
    }

    /**
     * Deletes cache
     *
     * @param string $filename
     *
     * @return bool
     */
    public function deleteCache($filename)
    {
        if ($this->findCache($filename)) {
            unlink($this->cachePath . $filename);
            return true;
        }
        return false;
    }

    // /**
    //  * Creates cache dir
    //  *
    //  * @param string $dir
    //  */
//    public function createCacheDir($dir)
//    {
//        if (!file_exists($this->cachePath . str_replace('/', '', $dir))) {
//            mkdir($this->cachePath . str_replace('/', '', $dir));
//        }
//        return TRUE;
//    }

    public function purgeCache()
    {
        foreach (new DirectoryIterator($this->cachePath) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                unlink($fileInfo->getPathname());
            }
        }
    }
}
