<?php

class Controller {

	public $layout = 'layout.default';

	public function render($file, $data = null, $return = false) {
		$out = $this->renderPartial($file, $data, true);

		$out = $this->renderPartial($this->layout, array('content' => $out), true);

		if ($return)
			return $out;
		else
			echo $out;
	}

	public function renderPartial($file, $data = null, $return = false) {
		if ($filename = $this->_findViewFile($file)) {
			return $this->renderInternal($filename, $data, $return);
		} else {
			throw new Exception('View ' . $file . ' not found.');
		}
	}

	public function renderInternal($_fileNC, $_dataNC, $_returnNC) {
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

	private function _findViewFile($file) {
		$paths = array(Core::app()->appDir . '/app/views/', Core::app()->coreDir . '/views/');

		foreach ($paths as $path) {
			$filename = $path . $file . '.php';
			if (file_exists($filename)) {
				return $filename;
			}
		}

		return false;
	}

}