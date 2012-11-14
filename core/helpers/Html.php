<?php

class Html {

	public static function link($text, $url, $options = array()) {
		return '<a href="' . self::url($url) . '">' . $text . '</a>';
	}

	public static function url($url) {
		if (!is_array($url)) {
			return self::url(array($url));
		}

		$script = '';
		if (Core::app()->cfg('url/addScriptName') == true) {
			$script = 'index.php';
		}
		$addon = '';
		$path = '';
		if (Core::app()->cfg('url/pathInsteadGet') == true) {
			$addon = '?p=';
			foreach (array_slice($url, 1) as $key => $entry) {
				$path .= '&' . $key . '=' . $entry;
			}
		}

		return Core::app()->baseUrl() . $script . $addon . $url[0] . $path;
	}

}
