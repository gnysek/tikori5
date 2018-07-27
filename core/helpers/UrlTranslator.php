<?php

class UrlTranslator
{
    static function getUrlToScope($website, $lang = '')
    {
        $scopeList = Core::app()->cfg('scopes', array());

        $scope = $website . (!empty($lang) ? ('-' . $lang) : '');

        if (array_key_exists($scope, $scopeList)) {
            $domains = $scopeList[$scope]['domains'];

            if (is_array($domains)) {
                $domain = Core::app()->getMode() != Core::MODE_PROD ? $domains[0] : $domains[1];
            } else {
                $domain = $domains;
            }

            return 'http://' . $domain . '/';
        }
    }
}