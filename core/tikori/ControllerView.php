<?php

class ControllerView extends TView
{

    protected $_widgets = array();

    /**
     * @param string $class
     * @param array $properties
     * @param bool $captureOutput
     * @return Widget|mixed
     */
    public function widget($class, $properties = array(), $captureOutput = false)
    {
        if ($captureOutput) {
            ob_start();
            ob_implicit_flush(false);
        }

        $widget = $this->_createWidget($class, $properties);
        $widget->onCall();

        if ($captureOutput) {
            return ob_get_clean();
        }

        return $widget;
    }

    /**
     * @param $uniquename string
     * @param $template string path
     * @param $data mixed data passed to template
     * @param int $time Time in seconds to look back. Set 0 to give infinity
     * @param array $tags Unique tags by which it will be cleared/stored
     * @return bool|string
     * @throws Exception
     */
    public function cachedBlock($uniquename, $template, $data, $time = 0, $tags = array())
    {
        if (Core::app()->lang->usingLanguages) {
            $uniquename .= '_' . Core::app()->lang->currentLanguage;
        }

        if (Core::app()->route->scope !== null) {
            $uniquename .= '_' . Core::app()->route->scope;
        }

        if (count($this->_themes)) {
            $uniquename .= sprintf('_%s_', implode('_', $this->_themes));
        }

        $block = new CacheableBlock($this, $uniquename, $tags);

        if ($time === false or $time === null) {
            $time = -1;
        }

        $exists = $block->checkCacheExists($time == 0 ? 0 : (time() - $time));

        if (!$exists or Core::app()->cfg('dev/no-cache', false) == true) {
            Profiler::addNotice('Cache [' . $uniquename . '] not found, or too old');
            $__cacheContent = $this->renderPartial($template, $data, true);
            $block->save($__cacheContent);
            return $__cacheContent;
        } else {
            Profiler::addNotice('Cache [' . $uniquename . '] found');
            return $block->load();
        }
    }

    public function renderCached($uniquename, $template, $data, $time = 0, $tags = array())
    {
        return $this->render(null, $this->cachedBlock($uniquename, $template, $data, $time, $tags));
    }

    /**
     * @param $class
     * @param $properties
     * @return Widget|mixed
     */
    protected function _createWidget($class, $properties)
    {
        $_widgetHash = strtolower($class);
        if (!array_key_exists($_widgetHash, $this->_widgets)) {
            $className = ucfirst($class) . 'Widget';
            $widget = new $className();
            /* @var $widget Widget */
            $widget->setupProperties($properties);
            $widget->onCreate();
            $this->_widgets[$_widgetHash] = $widget;
        } else {
            if (is_array($properties) and count($properties)) {
                $this->_widgets[$_widgetHash]->setupProperties($properties);
            }
        }
        return $this->_widgets[$_widgetHash];
    }
}
