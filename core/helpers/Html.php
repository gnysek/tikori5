<?php

class Html
{

    public static $sidAddon = '';

    public static function link($text, $url, $options = array())
    {
        if (trim(Core::app()->request->getRouterPath(), '/') == $url) {
            if (!empty($options['class'])) {
                $options['class'] = 'active ' . $options['class'];
            } else {
                $options['class'] = 'active';
            }
        }
        return '<a href="' . self::url($url) . '"' . ((empty($options['class']))
            ? '' : (' class="' . $options['class'] . '"')) . '>' . $text . '</a>';
    }

    public static function url($url = array())
    {
        if (!is_array($url)) {
            return self::url(array($url));
        }

        // external
        if (preg_match('/^http.*/', $url[0])) {
            return $url[0];
        }

        $script = '';
        if (Core::app()->cfg('url/addScriptName') == true) {
            $script = 'index.php/';
        }
        $addon = '';
        $path = '';

        if (Core::app()->route) {
            if (Core::app()->route->area != null) {
                if (!preg_match('#^//#', $url[0])) {
                    $url[0] = Core::app()->route->area . '/' . $url[0];
                }
            }
        }
        $url[0] = ltrim($url[0], '/');
        $url[0] = str_replace('//', '/', $url[0]);


        if (!empty($url[0])) {
            if (count($url) == 1) {
                if (Core::app()->cfg('default') == $url[0]) {
                    $url[0] = '';
                }
            } else {
                if (Core::app()->cfg('url/pathInsteadGet') == true) {
                    $addon = '?' . Request::GET_PATH_PARAM . '=';
                    foreach (array_slice($url, 1) as $key => $entry) {
                        $path .= '&' . $key . '=' . $entry;
                    }
                } else {
                    foreach (array_slice($url, 1) as $key => $entry) {
                        $path .= '/' . $key . '/' . $entry;
                    }
                }
            }
        }

        if (!empty(self::$sidAddon)) {
            $path = (!empty($addon)) ? '&' : '?';
            $path .= 'sid=' . self::$sidAddon;
        }

        return Core::app()->baseUrl() . $script . $addon . $url[0] . $path;
    }

}
