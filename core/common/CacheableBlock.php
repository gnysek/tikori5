<?php

class CacheableBlock
{

    protected $_tags = array('');
    protected $_templateFilename = '';
    protected $_cachedFilename = '';
    protected $_cachedRealname = '';
    protected $view = null;

    public static function clearByTags($tags = array())
    {
        foreach ($tags as $tag) {
            $path = Core::app()->cache->cachePath . '*__' . strtoupper(preg_replace('/[^a-z0-9]/', '', strtolower($tag))) . '__*';

            foreach (glob($path) as $file) {
                unlink($file);
            }
        }
    }

//    public static function new($tview, $filename = 'cache_', $tags = array())
//    {
//        return new CacheableBlock($tview, $filename, $tags);
//    }

    public function __construct($tview, $filename = 'cache_', $tags = array())
    {
        $fname = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace('_', '-', $filename)));

        if (empty($tags) or !is_array($tags)) {
            $tags = array('DEFAULT');
        }

        foreach ($tags as $tag) {
            $fname .= '__' . strtoupper(preg_replace('/[^a-z0-9]/', '', strtolower($tag))) . '__';
        }

        $fname .= '.cache';

        $this->_templateFilename = $filename;
        $this->_cachedFilename = $fname;
        $this->_cachedRealname = Core::app()->cache->cachePath . $fname;
        $this->_tags = $tags;

        $this->view = ($tview instanceof TView) ? $tview : new TView();
    }

    public function checkCacheExists($time = 0)
    {
        if (Core::app()->mode !== Core::MODE_PROD && Core::app()->request->isHardRefresh()) return false;

        if (file_exists($this->_cachedRealname)) {
            if ($time == 0 or filemtime($this->_cachedRealname) > $time) {
                return true;
            }
        }

        return false;
    }

    public function save($content = '')
    {
        file_put_contents($this->_cachedRealname, $content);
    }

    public function load()
    {
        return file_get_contents($this->_cachedRealname);
    }

//    public abstract function toHtml();
}
