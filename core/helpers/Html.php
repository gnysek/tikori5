<?php

class Html
{

    public static $sidAddon = '';
    public static $hrefActiveClass = 'active';
    public static $labelClass = 'col-sm-2 control-label';
    public static $formClass = 'form-control';
    const ACTIVE_BY_PATH = '_activeByPath';
    const SKIP_ACTIVE = '_skipActive';

    public static function link($text, $url, $options = array())
    {
        $_url = self::url($url);

        if (!isset($options[self::SKIP_ACTIVE])) {
            self::_linkCheckActiveClass($options, $_url);
        }

        return html::htmlTag('a', $options + array('href' => $_url, 'title' => htmlspecialchars(trim(strip_tags($text)))), $text);
    }

    public static function linkTagWrapped($wrap = 'li', $text = '', $url = '', $wrapOptions = array(), $options = array())
    {
        $fakeOptions = (!empty($options[self::ACTIVE_BY_PATH])) ? [self::ACTIVE_BY_PATH => $options[self::ACTIVE_BY_PATH]] : [self::ACTIVE_BY_PATH => '/' . preg_quote($url, '/') . '$/'];

        if (!isset($options[self::SKIP_ACTIVE])) {
            self::_linkCheckActiveClass($fakeOptions, $url);
        }

        if (isset($options[self::SKIP_ACTIVE])) {
            unset($options[self::SKIP_ACTIVE]);
        }

        $html = self::link($text, $url, $options);

        if (isset($fakeOptions['class']) and isset($wrapOptions['class'])) {
            $fakeOptions['class'] .= ' ' . $wrapOptions['class'];
            unset($wrapOptions['class']);
        }

        return self::htmlOpenTag($wrap, $fakeOptions + $wrapOptions) . $html . self::htmlCloseTag('li');
    }

    protected static function _linkCheckActiveClass(& $options, $url)
    {
        $url = str_replace(Core::app()->baseUrl(), '', $url); // as $url comes with http(s)
        $current = Core::app()->request != NULL ? trim(Core::app()->request->getRouterPath(), '/') : '';

        if (Core::app()->route) {
            if (Core::app()->route->area != null) {
                $url = Core::app()->route->area . '/' . $url;
            }
        }
        $inCurrentLink = ($current == $url);

        if (!empty($options[self::ACTIVE_BY_PATH])) {
            if ($inCurrentLink == false) {
                if (!is_array($options[self::ACTIVE_BY_PATH])) {
                    $options[self::ACTIVE_BY_PATH] = array($options[self::ACTIVE_BY_PATH]);
                }

                foreach ($options[self::ACTIVE_BY_PATH] as $pattern) {
                    if (!empty($pattern) and preg_match($pattern, $current)) {
                        $inCurrentLink = true;
                    }
                }

            }
            unset($options[self::ACTIVE_BY_PATH]);
        }

        //TODO: when error/exception raised, request is empty ?
        if ($inCurrentLink) {
            if (!empty($options['class'])) {
                $options['class'] = self::$hrefActiveClass . ' ' . $options['class'];
            } else {
                $options['class'] = self::$hrefActiveClass;
            }
        }
        return $options;
    }

    public static function isActive($url, $byPath = null)
    {
        $options = array();
        if (!empty($byPath)) {
            $options[self::ACTIVE_BY_PATH] = array($byPath);
        }

        $result = self::_linkCheckActiveClass($options, $url);

        return (array_key_exists('class', $result));
    }

    const SCOPE_KEY = '_scope';

    /**
     * @param string|array $url
     * @return string
     * @throws Exception
     */
    public static function url($url = array())
    {

        if (!is_array($url)) {
            return self::url(array($url));
        } else {
            if (count($url) == 0) {
                $url[0] = '';
            }
        }

        // external
        if (preg_match('/^(\/*)http.*/', $url[0])) { // in case / or // was added, include it here also
            return ltrim($url[0], '/');
        }

        $scope = null;
        if (array_key_exists(self::SCOPE_KEY, $url)) {
            $scope = $url[self::SCOPE_KEY];
            unset($url[self::SCOPE_KEY]);
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
        $get = '';

        if (Core::app()->route) {
            if (Core::app()->route->area != NULL) {
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
                        if (substr($key, 0, 1) == '?') {
                            $get .= (empty($get) ? '?' : '&') . substr($key, 1) . '=' . $entry;
                        } else if ($entry !== null) {
                            $path .= '/' . $key . '/' . $entry;
                        }
                    }
                }
            }
        }

        if (!empty(self::$sidAddon)) {
            $path = (!empty($addon)) ? '&' : '?';
            $path .= 'sid=' . self::$sidAddon;
        }

        return ($scope !== null ? UrlTranslator::getUrlToScope($scope) : Core::app()->baseUrl()) . $script . $addon . $url[0] . $path . $get;
    }

    public static function shortenUrl($url)
    {
        $sliced = trim(preg_replace('/(http(s)*\:\/\/(www\.)*)/i', '', $url), '/');
        if (strlen($sliced) > 30 and substr_count($sliced, '/') > 2) {
            $sliced = preg_replace('/^(.*?)\.([a-z\.]{0,6})\/(.*?)(.{0,20})$/i', '$1.$2/&hellip;$4', $sliced);
        }
        return $sliced;
    }

    public static function isPrintable($v) {
        return is_scalar($v);
    }

    public static function beginForm($action = '/', $method = 'post', $upload = false, $class = NULL)
    {
        return '<form' . ((!empty($class)) ? (' class="' . $class . '"') : '') . ' action="' . self::url($action) . '" method="' . $method . '"'
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

    public static function htmlTag($tag, $options = array(), $innerHtml = NULL)
    {
        if ($options === false or $options === NULL) {
            $options = array();
        }

        return ($innerHtml === NULL)
            ? self::htmlOpenTag($tag, $options, true)
            : (self::htmlOpenTag($tag, $options, false) . $innerHtml . self::htmlCloseTag($tag));
    }

    public static function htmlOpenTag($tag, $options = array(), $noInnerHtml = false)
    {
        $html = '<' . $tag;

        foreach ($options as $k => $v) {
            if ($v === null) {
                continue;
            }
            if (strncmp($k, '_', 1) === 0) {
                continue;
            }
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
     * @param TModel  $model
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

    /**
     * @param $model TModel
     *
     * @return string
     */
    public static function errorsInModel($model)
    {
        $errors = $model->getErrors();
        if (!empty($errors)) {
            return self::htmlTag('div', array('style' => 'color: red; font-size: 10px;'), implode('<br/>', $errors));
        }

        return '';
    }

    public static function labelModel($model, $field, $text = NULL)
    {
        return self::htmlTag(
            'label', array(
                'for'   => get_class($model) . '[' . $field . ']',
                'class' => self::$labelClass,
            ),
            empty($text) ? ucfirst(str_replace('_', ' ', $field)) : $text
        );
    }

    public static function textFieldModel($model, $field, $options = array(), $type = 'text')
    {
        $value = ($type === 'file') ? array() : array('value' => (is_scalar($model->$field) ? $model->$field : ''));
        return self::htmlTag(
            'input', $options + $value + array(
                'type' => $type,
                'name' => get_class($model) . '[' . $field . ']',
                'autocomplete' => 'off',
                'class' => self::$formClass . ' pure-u-1-3',
            )
        );

    }

    public static function textareaFieldModel($model, $field, $options = array())
    {
        return self::htmlTag(
            'textarea', $options + array(
                             'name' => get_class($model) . '[' . $field . ']',
                        ),
            $model->$field . ''
        );
    }

    public static function radioFieldModel($model, $field, $values = array(), $divider = NULL)
    {
        return self::radioField(get_class($model) . '[' . $field . ']', $values, $model->$field, $divider);
//        $html = '';
//        $i = 0;
//
//        foreach ($values as $k => $v) {
//            $opt = array(
//                'type'  => 'radio',
//                'name'  => get_class($model) . '[' . $field . ']',
//                'id'    => get_class($model) . '_' . $field . '_' . ++$i,
//                'value' => $k
//            );
//
//            if ($k == $model->$field) {
//                $opt['checked'] = 'checked';
//            }
//
//            $radio = self::htmlTag(
//                'input',
//                $opt
//            );
//
//            $html .= self::htmlTag(
//                'label', array(
//                    'for' => get_class($model) . '_' . $field . '_' . $i,
//                    'class' => 'radio-label'
//                    #'id'  => get_class($model) . '_' . $field,
//                ),
//                $radio . $v
//            );
//
//            $html .= $divider;
//        }
//
//        return $html;
    }

    public static function radioField($field, $values = [], $value = null, $divider = null)
    {
        $html = '';
        $i = 0;

        foreach ($values as $k => $v) {
            $i++;

            $opt = [
                'type'  => 'radio',
                'name'  => $field,
                'id'    => strtolower(str_replace(['[', ']', '_'], '_', $field)) . $i,
                'value' => $k,
            ];

            if ($k == $value) {
                $opt['checked'] = 'checked';
            }

            $radio = self::htmlTag('input', $opt);

            $html .= self::htmlTag('label', [
                'for'   => strtolower(str_replace(['[', ']', '_'], '_', $field)) . $i,
                'class' => 'radio-label'
                #'id'  => get_class($model) . '_' . $field,
            ], $radio . ' ' . $v);

            $html .= $divider . PHP_EOL;
        }

        return $html;
    }

    public static function checkboxFieldModel($model, $field, $value = '1')
    {
        $html = '';
        $opt = array(
            'type'  => 'checkbox',
            'name'  => get_class($model) . '[' . $field . ']',
            'id'    => get_class($model) . '_' . $field,
            'value' => $value,
        );

        if ($model->$field == $value) {
            $opt['checked'] = 'checked';
        }

        $html .= self::htmlTag('input', $opt);

        return $html;
    }

    public static function selectOptionModel($model, $field, $values = array(), $options = array())
    {
        $html = '';

        if (empty($options['class'])){
            $options['class'] = self::$formClass;
        }

        $html .= self::htmlOpenTag('select', array('name' => get_class($model) . '[' . $field . ']') + $options);
        foreach ($values as $key => $value) {
            $_options = array('value' => $key);
            if ($key == $model->$field) {
                $_options['selected'] = 'selected';
            }
            //$html .= self::htmlTag('option', array_merge($options, $_options), $value);
            $html .= self::htmlTag('option', $_options, $value);
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
        return '<link href="' . Core::app()->baseUrl() . $url . '" rel="icon" type="image/x-icon" />' . PHP_EOL;
    }
}
