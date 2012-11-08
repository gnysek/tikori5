<?php

class Html {

	public static function link($text, $url, $options = array()) {
		return '<a href="' . self::url($url) . '">' . $text . '</a>';
	}

	public static function url($url) {
		if (!is_array($url)) {
			return self::url(array($url));
		}

		$cfg = Core::app()->cfg('url');

		$script = '';
		if (!empty($cfg['addScriptName']) and $cfg['addScriptName'] == true) {
			$script = 'index.php';
		}
		$addon = '';
		$path = '';
		if (!empty($cfg['pathInsteadGet']) and $cfg['pathInsteadGet'] == true) {
			$addon = '?p=';
			foreach (array_slice($url, 1) as $key => $entry) {
				$path .= '&' . $key . '=' . $entry;
			}
		}

		return Core::app()->baseUrl() . $script . $addon . $url[0] . $path;
	}

}
