<?php

class Error {

	public static function display(Exception $exception) {
		$view = new Controller();
		$e = Core::app()->cfg('env');

		$files = array();

		foreach (array_reverse($exception->getTrace()) as $trace) {
			if (isset($trace['line'], $trace['file'])) {
				$files[] = self::getFile($trace['file'], $trace['line']);
			}
		}

		$files[] = self::getFile($exception->getFile(), $exception->getLine());

		$body = $view->renderPartial('error.fatal', array(
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'reqMethod' => $e['REQUEST_METHOD'],
				'reqPath' => $e['PATH_INFO'],
				'stack' => $exception->getTraceAsString(),
				'files' => $files,
			), true);

		echo $body;
		die();
	}

	private static function getFile($filename, $line) {
		$html = array();

		if ($file = @file($filename)) {

			$html[] = '<br/>';
			
			$dispName = str_replace(Core::app()->appDir . DIRECTORY_SEPARATOR, '', $filename);
			$dispName = str_replace('\\', '/', $dispName);
			
			$html[] = '<span class="pink">' . $dispName . ':' . $line . '</span><br/>' . PHP_EOL;
			$html[] = '<div class="code"><pre>';
			$code = array();
			for ($i = max(0, $line - 11); $i < min(count($file), $line + 10); $i++) {
//					if (!preg_match('/[a-z\/]/i', $file[$i]))
//						continue;

				if ($i == $line - 1) {
					$code[] = '<div class="err">';
				}
				$code[] = '<span class="num">' . sprintf('%05d', $i + 1) . '</span>';
				$code[] = htmlspecialchars(str_replace(array("\n", "\r", "\r\n"), '', $file[$i]));
				if ($i == $line - 1) {
					$code[] = '</div>';
				} else {
					$code[] = PHP_EOL;
				}
			}
			$html[] = implode('', $code);
			$html[] = '</pre></div>';
		}

		return implode('', $html);
	}

}
