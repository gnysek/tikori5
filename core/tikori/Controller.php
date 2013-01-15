<?php

class Controller {

	public $layout = '//layout.default';
	public $controller = 'default';
	public $action = 'default';
	public $params = array();
	public $area = '';
	public $checkPermissions = false;

	public function __construct($area = null) {
		$this->area = $area;
		if (is_callable($this,'afterConstruct')) {
			$this->afterConstruct();
		}
	}

	public function setController($controller = '') {
		$this->controller = $controller;
	}

	public function setAction($action = '') {
		$this->action = $action;
	}

	public function getControllerFullName() {
		return ucfirst($this->controller) . 'Controller';
	}

	public function setParams($params) {
		$this->params = array_merge($this->params, $params);
	}

	public function httpStatusAction($status = 404) {
		Core::app()->response->status($status);
		$this->render('http404', array('status' => $status, 'message' => Response::getMessageForCode($status)));
	}

//	public function defaultAction() {
//		throw new RouteNotFoundException('Unknown action');
//	}

	public function render($file = null, $data = null, $return = false) {
		if (!empty($file) && $this->viewExists($file)) {
			$out = $this->renderPartial($file, $data);
		} else {
			$out = (string) $data;
		}

		$out = $this->renderPartial($this->layout, array('content' => $out));

		if ($return)
			return $out;
		else
			echo $out;
	}

	public function renderPartial($file, $data = null, $return = true) {
		if ($filename = $this->_findViewFile($file)) {
			return $this->renderInternal($filename, $data, $return);
		} else {
			throw new Exception('View ' . $file . ' not found.');
		}
	}

	public function renderInternal($_fileNC, $_dataNC = null, $_returnNC = false) {
		if (is_array($_dataNC)) {
			extract($_dataNC, EXTR_PREFIX_SAME, 'data');
		} else {
			$data = $_dataNC;
		}

		if ($_returnNC) {
			ob_start();
			ob_implicit_flush(false);
			Log::addLog('Rendering <tt>' . str_replace(Core::app()->appDir, '', $_fileNC) . '</tt>');
			require($_fileNC);
			return ob_get_clean();
		} else {
			require $_fileNC;
		}
	}

	public function viewExists($view) {
		return ($this->_findViewFile($view) !== false);
	}

	protected function _findViewFile($file) {
		$paths = array();

		if (substr($file, 0, 2) != '//') {
			$paths[] = Core::app()->appDir . '/views/' . $this->controller . '/';
			$paths[] = Core::app()->coreDir . '/views/' . $this->controller . '/';

			$modules = Core::app()->cfg('modules');
			if (!empty($modules)) {
				foreach ($modules as $module => $config) {
					$module = strtolower($module);
					$paths[] = Core::app()->appDir . '/modules/' . $module . '/views/'; /* strtolower($this->controller) . */
					$paths[] = Core::app()->coreDir . '/modules/' . $module . '/views/'; /* strtolower($this->controller) . */
				}
			}
		}

		$paths[] = Core::app()->appDir . '/views/';
		$paths[] = Core::app()->coreDir . '/views/';

		if (!empty($this->area)) {
			foreach ($paths as $entry) {
				array_unshift($paths, $entry . $this->area . '/');
			}
		}

		$file = ltrim($file, '/');

		foreach ($paths as $path) {
			$filename = $path . $file . '.php';
			if (file_exists($filename)) {
				return $filename;
			}
		}

		return false;
	}

	public function widget($class, $properties, $captureOutput = false) {
		if ($captureOutput) {
			ob_start();
			ob_implicit_flush(false);
		}

		$widget = $this->_createWidget($class, $properties);
		$widget->run();

		if ($captureOutput) {
			return ob_get_clean();
		}

		return $widget;
	}

	private function _createWidget($class, $properties) {
		$className = ucfirst($class) . 'Widget';
		$widget = new $className;
		$widget->setupProperties($properties);
		$widget->init();
		return $widget;
	}

}
