<?php

class TConfig
{

    private $_data = array();
    #private $_checksum = array();

    public function clear() {
        $this->_data = array();
    }

    public $setOnNotFound = true;

    /**
     * Returns config value if exists and default value if not found
     *
     * @param type  $path    Config path to get. Can be partial or, full, ex.: path, path/to path/to/something
     * @param mixed $default null | Value returned as default
     *
     * @return mixed Config value
     */
    public function get($path = null, $default = null)
    {
        return $this->_getNode($this->_data, trim($path, '/'), trim($path, '/'), $default);
    }

    protected function _getNode($node, $path, $fullPath, $default = null)
    {
        $_paths = explode('/', $path);

        if (!is_array($_paths) or count($_paths[0]) < 1) {
            if (strlen($fullPath) == 0) {
                return $this->_data;
            }
        } else {
            if (count($_paths) == 1) {
                // TODO: what if asking for config/node/node, but only config exists ?
                if (is_array($node) and array_key_exists($path, $node)) {
                    return $node[$path];
                }
                #return $default;
            } else {
                if (array_key_exists($_paths[0], $node) and is_array($node)) {
                    return $this->_getNode($node[$_paths[0]], implode('/', array_slice($_paths, 1)), $fullPath, $default);
                }
            }
        }

        Profiler::addNotice('Cannot find cfg for ' . $fullPath);

        if ($this->setOnNotFound) {
            Profiler::addNotice('> Setting ' . $fullPath . ' to null for future queries.');
            $this->set($fullPath, $default);
        }

        return $default;
    }

    /**
     * Sets $path value to $value. Defaults sets to true.
     * Usage:
     * set('param')
     * set('param/another')
     * set('param', 'ok')
     *
     * @param mixed $path      Path to append
     * @param mixed $value     true | Value to set
     * @param mixed $overwrite Whether to overwrite setting if already exists
     *
     * @return bool Wheter value was set or not
     */
    public function set($path, $value = true, $overwrite = false)
    {
        return $this->_setNode($this->_data, trim($path, '/'), $value, $overwrite);
    }

    protected function _setNode(&$node, $path, $value = true, $overwrite = false)
    {
        $_paths = explode('/', $path);

        if (count($_paths) == 0) {
            throw new Exception('Cannot set config value for empty path.');
        } else {
            if (count($_paths) == 1) {
                if ($overwrite == true or !array_key_exists($path, $node)) {
                    $node[$path] = $value;
                    return true;
                }
                return false;
            } else {
                if (!array_key_exists($_paths[0], $node)) {
                    // if one of subnodes from path to set doesn't yet exists
                    $node[$_paths[0]] = array();
                }
                return $this->_setNode($node[$_paths[0]], implode('/', array_slice($_paths, 1)), $value, $overwrite);
            }
        }
        return false;
    }

    public function delete($item)
    {
        throw new Exception('Unimplemented');
    }

    //load('x') loads x.json, load('forum:x') loads modules/forum/config/x.json :)
    public function load($file, $reload = false)
    {
        $atLeastOneFound =  true;
        foreach (\Core::app()->namespaces as $path) {

            foreach (array('', '.dev') as $suffix) {
                $filename = $path . '/config/' . $file . $suffix . '.json';

                if (file_exists($filename)) {
                    $data = file_get_contents($filename);

                    $decoded = json_decode($data, true);
                    if ($decoded == null) {
                        throw new Exception('Config isn\'t valid JSON file.');
                    }

                    //$this->_data = array_merge_recursive($this->_data, $decoded);
                    $this->_data = array_replace_recursive($this->_data, $decoded);
                    #$this->_checksum = md5_file($filename);

                    $atLeastOneFound = true;
                }
            }
        }

        if ($atLeastOneFound) {
            return true;
        } else {
            throw new Exception('Config file ' . $file . '.json doesn\'t exists');
        }
    }

    /*public function checksum()
    {
        return $this->_checksum;
    }*/

    public function save($file, $item)
    {
        throw new Exception('Unimplemented');
    }

//	private function _configMerge($key, $value) {
//
//		if (is_array($value)) {
//			foreach ($value as $k => $v) {
//				$this->_configMerge($key . '/' . $k, $v);
//			}
//		} else {
//			$this->_flatData[$key] = $value;
//		}
//
//		return true;
//	}
}
