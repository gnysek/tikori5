<?php

class Lang
{

    public $languages = array();
    public $translations = array();
    public $defaultLanguage = 'en';
    public $currentLanguage = 'en';

    public function __()
    {
        $args = func_get_args();
        return $this->translate($args);
    }


    public function loadLanguages()
    {
        if (!Core::app()->cfg('languages')) {
            return;
        }

        $avaliableLanguages = Core::app()->cfg('languages/list');

        if (count($avaliableLanguages) < 1) {
            return; //no languages?
        }

        $this->defaultLanguage = $this->currentLanguage = $avaliableLanguages[0];

        // setup current language
        if (Core::app()->cfg('languages/type') == 'subdomains') {
            $subdomains = Core::app()->request->env['tikori.subdomains'];
            if (count($subdomains)) {
                $subdomain = $subdomains[0];
                foreach ($avaliableLanguages as $lang) {
                    if ($lang == $subdomain) {
                        $this->currentLanguage = $lang;
                        break;
                    }
                }
            }
        } else {
            //areas, todo
        }

        foreach (array('core', 'app') as $namespace) {

            if ($namespace == 'core') {
                $autodir = TIKORI_FPATH;
            } else {
                $autodir = rtrim(TIKORI_ROOT . DIRECTORY_SEPARATOR . $namespace, '/');
            }
            $files = glob($autodir . '/locale/*.php');

            foreach ($files as $filename) {

                $language = preg_replace('#([a-z]+)\.php#i', '$1', basename($filename));
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

    public function translate($args)
    {
        if (empty($args)) {
            return '';
        }

        $text = $args[0];

        if (in_array($this->currentLanguage, $this->languages)) {
            if (array_key_exists($args[0], $this->translations[$this->currentLanguage])) {
                $text = $this->translations[$this->currentLanguage][$text];
            }
        }

        $args = array_slice($args, 1);

        if (count($args) > 0) {
            foreach ($args as $v) {
                $text = str_replace('%s', $v, $text);
            }
        }

        return $text;
    }
}
