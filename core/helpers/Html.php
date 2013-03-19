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
                    $addon = '?' . Request::ROUTE_TOKEN . '=';
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

    public static function beginForm($action = '/', $method = 'post')
    {
        return '<form action="' . $action . '" method="' . $method . '">';
    }

    public static function endForm()
    {
        return '</form>';
    }

    public static function warnings($model)
    {
        return '';
    }

    public static function htmlTag($tag, $options = array(), $innerHtml = null)
    {
        return ($innerHtml === null)
            ? self::htmlOpenTag($tag, $options, true)
            : (self::htmlOpenTag($tag, $options, false) . $innerHtml . self::htmlCloseTag($tag));
    }

    public static function htmlOpenTag($tag, $options = array(), $noInnerHtml = false)
    {
        $html = '<' . $tag;

        foreach ($options as $k => $v) {
            $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
        }

        if ($noInnerHtml) {
            $html .= '/>';
        } else {
            $html .= '>';
        }

        return $html;
    }

    public static function htmlCloseTag($tag)
    {
        return '</' . $tag . '>';
    }

    public static function labelModel($model, $field, $text = null)
    {
        return self::htmlTag(
            'label', array(
                          'for' => get_class($model) . '[' . $field . ']',
                          #'id'  => get_class($model) . '_' . $field,
                     ),
            empty($text) ? ucfirst($field) : $text
        );
    }

    public static function textFieldModel($model, $field)
    {
        return self::htmlTag(
            'input', array(
                          'name'  => get_class($model) . '[' . $field . ']',
                          'value' => $model->$field
                     )
        );

    }

    public static function textareaFieldModel($model, $field)
    {
        return self::htmlTag(
            'textarea', array(
                             'name' => get_class($model) . '[' . $field . ']',
                        ),
            $model->$field . ''
        );
    }

    public static function radioFieldModel($model, $field, $options = array(), $divider = null)
    {
        $html = '';
        $i = 0;

        foreach ($options as $k => $v) {
            $opt = array(
                'type'  => 'radio',
                'name'  => get_class($model) . '[' . $field . ']',
                'id'    => get_class($model) . '_' . $field . '_' . ++$i,
                'value' => $k
            );

            if ($k == $model->$field) {
                $opt['checked'] = 'checked';
            }

            $html .= self::htmlTag(
                'input',
                $opt
            );

            $html .= self::htmlTag(
                'label', array(
                              'for' => get_class($model) . '_' . $field . '_' . $i,
                              #'id'  => get_class($model) . '_' . $field,
                         ),
                $v
            );
            $html .= $divider;
        }

        return $html;
    }

    public static function submitButton($text = 'Submit')
    {
        return self::htmlTag('input', array('type' => 'submit', 'value' => $text));
    }
}
