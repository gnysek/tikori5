<?php

class Html {

	public static function link($text, $url, $options = array()) {
		if (trim(Core::app()->request->getRouterPath(), '/') == $url) {
			if (!empty($options['class'])) {
				$options['class'] = 'active ' . $options['class'];
			} else {
				$options['class'] = 'active';
			}
		}
		return '<a href="' . self::url($url) . '"' . ((empty($options['class'])) ? '' : (' class="' . $options['class'] . '"')) . '>' . $text . '</a>';
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

		if (!empty($url[0])) {
			if (Core::app()->cfg('url/pathInsteadGet') == true) {
				$addon = '?' . Request::GET_PATH_PARAM . '=';
				foreach (array_slice($url, 1) as $key => $entry) {
					$path .= '&' . $key . '=' . $entry;
				}
			}
		}

		return Core::app()->baseUrl() . $script . $addon . $url[0] . $path;
	}

}
