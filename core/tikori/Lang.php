<?php

class Lang
{

    public $languages = array();
    protected $_searchedLangauges = array();
    public $translations = array();
    public $defaultLanguage = 'en';
    public $currentLanguage = 'en';
    public $usingLanguages = false;

    function __construct()
    {
        if (!Core::app()->cfg('languages')) {
            return;
        }

        $avaliableLanguages = Core::app()->cfg('languages/list');

        if (count($avaliableLanguages) < 1) {
            return; //no languages?
        }

        $this->usingLanguages = true;
        $this->defaultLanguage = $this->currentLanguage = $avaliableLanguages[0];

        // setup current language
        if (Core::app()->cfg('languages/type') == 'subdomains') {
            $subdomains = Core::app()->request->get('tikori.subdomains');
            $subdomainLanguageLinks = Core::app()->cfg('languages/subdomains');
            if (count($subdomains) && count($subdomainLanguageLinks)) {
                $currentSubdomain = $subdomains[0]; //take lowest subdomain, for example ab from ab.bc.de.ef.com
                foreach ($subdomainLanguageLinks as $language => $subdomainNeeded) {
                    if ($currentSubdomain == $subdomainNeeded && in_array($language, $avaliableLanguages)) {
                        $this->currentLanguage = $language;
                        break;
                    }
                }
            }
        } elseif (Core::app()->cfg('languages/type') == 'domains') {
            $domains = Core::app()->cfg('languages/domains');
            if (count($domains)) {
                $currentDomain = Core::app()->request->get('tikori.domain');
                foreach ($domains as $language => $domain) {
                    if ($currentDomain == $domain && in_array($language, $avaliableLanguages)) {
                        $this->currentLanguage = $language;
                        break;
                    }
                }
            }
        } else {
            //areas, todo
        }

        $this->loadLanguages($this->currentLanguage);
    }


    public function __()
    {
        $args = func_get_args();
        return $this->translate($args);
    }


    public function loadLanguages($lang)
    {
//        if (!Core::app()->cfg('languages')) {
//            return;
//        }
//
//        $avaliableLanguages = Core::app()->cfg('languages/list');
//
//        if (count($avaliableLanguages) < 1) {
//            return; //no languages?
//        }

//        $this->usingLanguages = true;
//        $this->defaultLanguage = $this->currentLanguage = $avaliableLanguages[0];

//        // setup current language
//        if (Core::app()->cfg('languages/type') == 'subdomains') {
//            $subdomains = Core::app()->request->get('tikori.subdomains');
//            $subdomainLanguageLinks = Core::app()->cfg('languages/subdomains');
//            if (count($subdomains) && count($subdomainLanguageLinks)) {
//                $currentSubdomain = $subdomains[0]; //take lowest subdomain, for example ab from ab.bc.de.ef.com
//                foreach ($subdomainLanguageLinks as $language => $subdomainNeeded) {
//                    if ($currentSubdomain == $subdomainNeeded && in_array($language, $avaliableLanguages)) {
//                        $this->currentLanguage = $language;
//                        break;
//                    }
//                }
//            }
//        } elseif (Core::app()->cfg('languages/type') == 'domains') {
//            $domains = Core::app()->cfg('languages/domains');
//            if (count($domains)) {
//                $currentDomain = Core::app()->request->get('tikori.domain');
//                foreach ($domains as $language => $domain) {
//                    if ($currentDomain == $domain && in_array($language, $avaliableLanguages)) {
//                        $this->currentLanguage = $language;
//                        break;
//                    }
//                }
//            }
//        } else {
//            //areas
//        }

        if (!in_array($lang, $this->_searchedLangauges)) {
            $this->_searchedLangauges[] = $lang;
        } else {
            return; //already searched/loaded
        }

        foreach (array('core', 'app') as $namespace) {

            if ($namespace == 'core') {
                $autodir = TIKORI_FPATH;
            } else {
                $autodir = rtrim(TIKORI_ROOT . DIRECTORY_SEPARATOR . $namespace, '/');
            }

            $files = glob($autodir . '/locale/' . $lang . '/*.php');

            foreach ($files as $filename) {

                $language = $lang;//preg_replace('#([a-z]+)\.php#i', '$1', basename($filename));
                if (!in_array($language, $this->languages)) {
                    $this->languages[] = $language;
                    $this->translations[$language] = array();
                }

                $file = fopen($filename, 'r');
                $lang = array();
                while ($data = fgetcsv($file, NULL, ',')) {
                    if (count($data) == 2) {
                        $this->translations[$language][$data[0]] = $data[1];
                    }
                }
                fclose($file);
            }
        }
    }

    public function translateTo($lang, $args) {
        if (empty($args)) {
            //return '';
            throw new Exception('Tryint to translate empty chain');
        }

        $text = $args[0];

        if (in_array($lang, $this->languages)) {
            if (array_key_exists($text, $this->translations[$lang])) {
                $text = $this->translations[$lang][$text];
            }
        }

        $args = array_slice($args, 1);

        if (count($args) > 0) {
            foreach ($args as $v) {
                $text = preg_replace('/%s/', $v, $text, 1);
            }
        }

        return $text;
    }

    public function translate($args)
    {
        return $this->translateTo($this->currentLanguage, $args);
    }
}
