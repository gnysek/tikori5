<?php

class Error {
	
	public static function registerErrors(){
		set_exception_handler(array('Error', 'exh'));
		set_error_handler(array('Error', 'erh'), E_ALL);
	}

	public static function exh(Exception $exception) {
		echo Error::display($exception);
		die();
	}

	public static function erh($errno, $errstr, $errfile, $errline, $errcontext) {
		echo Error::display(new Exception($errstr, $errno), array('file' => $errfile, 'line' => $errline));
		die();
	}

	public static function display(Exception $exception, $staticData = null) {
		$view = new Controller();
		$e = Core::app()->cfg('env');

		$files = array();

		foreach (array_reverse($exception->getTrace()) as $trace) {
			if (isset($trace['line'], $trace['file'])) {
				$files[] = self::getFile($trace['file'], $trace['line'], $trace);
			}
		}

		if ($staticData === null) {
			$files[] = self::getFile($exception->getFile(), $exception->getLine(), array('class' => 'Error::display()'));
		}

		$body = $view->renderPartial('error.fatal', array(
			'message' => $exception->getMessage(),
			'file' => (is_array($staticData)) ? $staticData['file'] : $exception->getFile(),
			'line' => (is_array($staticData)) ? $staticData['line'] : $exception->getLine(),
			'reqMethod' => $e['REQUEST_METHOD'],
			'reqPath' => $e['PATH_INFO'],
			'files' => $files,
			), true);

		echo $body;
		die();
	}

	private static function getFile($filename, $line, $trace = array()) {
		$html = array();

		if ($file = @file($filename)) {

//			$html[] = '<br/>';

			$dispName = str_replace(Core::app()->appDir . DIRECTORY_SEPARATOR, '', $filename);
			$dispName = str_replace('\\', '/', $dispName);

			$index = preg_replace('#[^a-z]#i', '', str_replace('.php', '', $dispName)) . $line;

			$html[] = '<p class="pink">' . $dispName . ':' . $line . '</p>' . PHP_EOL;
			$html[] = '<p><code class="prettyprint lang-php highlight" onclick="$(\'#' . $index . '\').toggle();">';
			$html[] = '<span class="num nocode">' . sprintf('%05d', $line) . '</span>';
			if (!empty($trace['class']))
				$html[] = $trace['class'];
			if (!empty($trace['type']))
				$html[] = $trace['type'];
			if (!empty($trace['function'])) {
				$html[] = $trace['function'] . '(';
				if (!empty($trace['args'])) {
					$args = array();
					foreach ($trace['args'] as $arg) {
						if (is_string($arg) or is_numeric($arg)) {
							if (is_string($arg)) {
								if (strlen($arg) > 15)
									$arg = substr($arg, 0, 15) . '&hellip;';
								$arg = '\'' . $arg . '\'';
							}
							$args[] = $arg;
						} else {
							$args[] = ucfirst(gettype($arg));
						}
					}
					$html[] = implode(', ', $args);
				}
				$html[] = ')';
			}
			$html[] = '</code></p>';

//			$html[] = var_export($trace, true);

			$html[] = '<div class="code hidden" id="' . $index . '"><pre class="prettyprint lang-php">';
			$code = array();
			for ($i = max(0, $line - 11); $i < min(count($file), $line + 10); $i++) {
//					if (!preg_match('/[a-z\/]/i', $file[$i]))
//						continue;

				if ($i == $line - 1) {
					$code[] = '<div class="err">';
				}
				$code[] = '<span class="num nocode">' . sprintf('%05d', $i + 1) . '</span>';
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
