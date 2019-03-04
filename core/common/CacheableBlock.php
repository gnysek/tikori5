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
                if (file_exists($file)) {
                    unlink($file);
                }
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

    /**
     * @param int $time Set to 0 to be infinity
     * @return bool
     */
    public function checkCacheExists($time = 0)
    {
        if (Core::app()->mode !== Core::MODE_PROD && Core::app()->request->isHardRefresh()) return false;

        if (file_exists($this->_cachedRealname)) {
            if ($time == 0 or filemtime($this->_cachedRealname) > $time) {
                if (Core::app()->hasLoadedModule('Toolbar')) {
                    Core::app()->toolbar->putValueToTab('Cache', sprintf(
                            'Checked that <code>%s</code> cache is older than <kbd>%ss</kbd> and it exist, it will be purged in <kbd>%ss</kbd>.<br>',
                            $this->_cachedRealname,
                            time() - $time,
                            $time == 0 ? 0 : ((time() - $time) - (time() - filemtime($this->_cachedRealname))))
                    ,  Core::app()->toolbar->getNotificationsNumberOnTab('Cache') + 1);
                }
                return true;
            } else {
                if (Core::app()->hasLoadedModule('Toolbar')) {
                    Core::app()->toolbar->putValueToTab('Cache', sprintf(
                            'Checked that <code>%s</code> cache is older than <kbd>%ss</kbd> and it exist, it will be purged in <kbd>NOW</kbd>.<br>',
                            $this->_cachedRealname,
                            time() - $time)
                    , Core::app()->toolbar->getNotificationsNumberOnTab('Cache') + 1);
                }
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
