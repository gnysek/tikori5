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
     * @param type $filename
     * @param type $content
     *
     * @return type
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
     * @param type $filemane filename to load
     * @param type $defaultContent default content returned
     *
     * @return type
     */
    public function loadCache($filemane, $defaultContent = null, $dontReturnMsg = false)
    {
        if ($this->findCache($filemane)) {
            return file_get_contents($this->cachePath . $filemane);
        } else {
            return ($defaultContent === null) ? (($dontReturnMsg === false) ? '[Cache is empty]' : $defaultContent)
                : $defaultContent;
        }
    }

    /**
     * Gets last modification time
     *
     * @param type $filename
     * @param type $check
     * @param type $distance
     *
     * @return type
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
