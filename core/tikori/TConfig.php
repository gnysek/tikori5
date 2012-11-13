<?php

class TconfigAlt {

	public function get($item, $default = null) {
		
	}

	public function set($item, $value, $protect = false) {
		
	}

	public function delete($item) {
		
	}

	//load('x') loads x.json, load('forum:x') loads modules/forum/config/x.json :)
	public function load($file, $reload = false) {
		
	}

	public function save($file, $item) {
		
	}

}

/**
 * Returns cfg data
 * @package tikori
 */
class TConfig {

	private $_data = array();
	private $_position = null;
	private static $tab = -1;

	public function __construct($data = array()) {
		is_array($data) OR $data = array($data);

		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$this->_data[$k] = new Config($v);
			} else {
				$this->_data[$k] = $v;
			}
		}

		return $this;
	}

	public function reconfigure($key, $data) {
		if (is_array($data)) {
			$data = new Config($data);
		}
		$this->_data[$key] = $data;
		return true;
	}

	/**
	 * Returns config value for $key
	 * 
	 * @param string $key Key to read cfg value
	 * @return mixed
	 */
	public function __get($name) {
		if (array_key_exists($name, $this->_data)) {
			return $this->_data[$name];
		}
//		return new Config();
	}

	public function __toString() {
		$str = '';
		TConfig::$tab++;
		foreach ($this->_data as $key => $v) {
			$str .= PHP_EOL . str_repeat("\t", TConfig::$tab) . $key . ' => ' . $v;
		}
		TConfig::$tab--;
		return $str;
	}

	public function toArray() {
		return $this->_data;
	}

}

/**
 * @property bool $addScriptName Whether add index.php to URL 
 * @property bool $pathInsteadGet Whether add route as GET ?r= param instead nice SEO path 
 */
class DefC_Url extends TConfig {

	public static function getDefValues() {
		return array(
			'addScriptName' => true,
			'pathInsteadGet' => true,
		);
	}

}

/**
 * @property bool $dblink Database PDO link 
 * @property bool $dbuser Database User 
 * @property bool $dbpass Database password 
 * @property bool $dbprefix Prefix to prepend table names, leave empty or use somethin like tbl_
 */
class DefC_Db extends TConfig {

	public static function getDefValues() {
		return array(
			'dblink' => '',
			'dbuser' => '',
			'dbpass' => '',
			'dbprefix' => '',
		);
	}

}
