<?php

class TConfig {

	private $_data = array();

	/**
	 * Returns config value if exists and default value if not found
	 * @param type $item Config path to get. Can be partial or, full, ex.: path, path/to path/to/something
	 * @param mixed $default null | Value returned as default
	 * @return mixed Config value
	 */
	public function get($item, $default = null) {
		return $this->_getNode($this->_data, trim($item, '/'), $default);
	}

	private function _getNode($node, $item, $default = null) {
		$paths = explode('/', $item);
		if (count($paths) == 1) {
			if (array_key_exists($item, $node))
				return $node[$item];
			return $default;
		} else {
			if (array_key_exists($paths[0], $node)) {
				return $this->_getNode($node[$paths[0]], implode('/', array_slice($paths, 1)), $default);
			}
		}
		return $default;
	}

	public function set($item, $value, $overwrite = false) {
		if ($overwrite == true or array_key_exists($item, $this->_data)) {
			$this->_data[$item] = $value;
			return true;
		}
		return false;
	}

	public function delete($item) {
		
	}

	//load('x') loads x.json, load('forum:x') loads modules/forum/config/x.json :)
	public function load($file, $reload = false) {
		foreach (Core::app()->autoloadPaths as $path) {
			$filename = $path . '/config/' . $file . '.json';

			if (file_exists($filename)) {
				$data = file_get_contents($filename);

				$decoded = json_decode($data, true);
				if ($decoded == null) {
					throw new Exception('Config isn\'t valid JSON file.');
				}
				
				$this->_data = $decoded;

				return true;
			}
		}

		throw new Exception('Config file ' . $file . '.json doesn\'t exists');
	}

	public function save($file, $item) {
		
	}

	private function _configMerge($key, $value) {

		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$this->_configMerge($key . '/' . $k, $v);
			}
		} else {
			$this->_flatData[$key] = $value;
		}

		return true;
	}

}