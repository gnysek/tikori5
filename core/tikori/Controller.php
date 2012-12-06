<?php

class Controller {

	public $layout = 'layout.default';
	public $controller = '';
	
	public function setController($controller){
		$this->controller = $controller;
	}

	public function render($file, $data = null, $return = false) {
		$out = $this->renderPartial($file, $data);

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
			require($_fileNC);
			return ob_get_clean();
		} else {
			require $_fileNC;
		}
	}

	public function viewExists($view) {
		return $this->_findViewFile($view);
	}

	private function _findViewFile($file) {
		$paths = array(
			Core::app()->appDir . '/views/' . $this->controller . '/',
			Core::app()->coreDir . '/views/' . $this->controller . '/',
			Core::app()->appDir . '/views/',
			Core::app()->coreDir . '/views/',
		);

		foreach ($paths as $path) {
			$filename = $path . $file . '.php';
			if (file_exists($filename)) {
				return $filename;
			}
		}

		return false;
	}

}