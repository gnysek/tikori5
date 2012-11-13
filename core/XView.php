<?php

class XView {

	public function render($filename, $data = array(), $return = false) {
		$fileFind = false;
		$response = '';

		foreach ($data as $k => $v) {
			$this->$k = $v;
		}

		foreach (array('app/views', 'core/views') as $path) {
			if (file_exists($path . '/' . $filename . '.php')) {
				$fileFind = true;
				ob_start();
				include $path . '/' . $filename . '.php';
				$response = ob_get_clean();
			}
		}

//		var_dump($response);
		
		if ($return) {
			return $response;
		} else {
			echo $response;
		}
	}

}
