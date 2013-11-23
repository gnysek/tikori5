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

        return html::htmlTag('a', $options + array('href' => self::url($url)), $text);
//        return '<a href="' . self::url($url) . '"' . implode(' ', $opt) . '>' . $text . '</a>';
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
            $script = 'index.php';
            if (Core::app()->cfg('url/pathInsteadGet') == false) {
                $script .= '/';
            }
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
                //TODO: check that it should be moved higher
                if (Core::app()->cfg('url/pathInsteadGet') == true) {
                    $addon = '?' . Request::ROUTE_TOKEN . '=';
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

    public static function beginForm($action = '/', $method = 'post', $upload = false, $class = null)
    {
        return '<form' . ((!empty($class)) ? (' class="' . $class . '"') : '') . '  action="' . self::url($action) . '" method="' . $method . '"'
        . (($upload) ? ' enctype="multipart/form-data"' : '') . '>';
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
        if ($options === false or $options === null) {
            $options = array();
        }

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

    /**
     * @param Model  $model
     * @param string $field
     *
     * @return string Html code
     */
    public static function errorModel($model, $field)
    {
        if ($model->hasErrorsField($field)) {
            return self::htmlTag('div', array('style' => 'color: red; font-size: 10px;'), implode('<br/>', $model->getErrorsField($field)));
        }

        return '';
    }

    public static function labelModel($model, $field, $text = null)
    {
        return self::htmlTag(
            'label', array(
                          'for' => get_class($model) . '[' . $field . ']',
                          #'id'  => get_class($model) . '_' . $field,
                     ),
            empty($text) ? ucfirst(str_replace('_', ' ', $field)) : $text
        );
    }

    public static function textFieldModel($model, $field, $options = array(), $type = 'text')
    {
        $value = ($type === 'file') ? array() : array('value' => $model->$field);
        return self::htmlTag(
            'input', $options + $value + array(
                'type' => $type,
                'name' => get_class($model) . '[' . $field . ']',
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

    public static function radioFieldModel($model, $field, $values = array(), $divider = null)
    {
        $html = '';
        $i = 0;

        foreach ($values as $k => $v) {
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
                'label', array(
                              'for' => get_class($model) . '_' . $field . '_' . $i,
                              #'id'  => get_class($model) . '_' . $field,
                         ),
                $v
            );

            $html .= self::htmlTag(
                'input',
                $opt
            );

            $html .= $divider;
        }

        return $html;
    }

    public static function selectOptionModel($model, $field, $values = array(), $options = array())
    {
        $html = '';

        $html .= self::htmlOpenTag('select', array('name' => get_class($model) . '[' . $field . ']') + $options);
        foreach ($values as $key => $value) {
            $_options = array('value' => $key);
            if ($key == $model->$field) {
                $_options['selected'] = 'selected';
            }
            $html .= self::htmlTag('option', array_merge($options, $_options), $value);
        }
        $html .= self::htmlCloseTag('select');

        return $html;
    }

    public static function submitButton($text = 'Submit', $options = array())
    {
        return self::htmlTag('input', array('type' => 'submit', 'value' => $text) + $options);
    }

    public static function favicon($url)
    {
        return '<link href="' . Core::app()->baseUrl() . $url . '" rel="icon" type="image/x-icon" />';
    }
}
