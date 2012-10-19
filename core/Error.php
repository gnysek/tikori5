<?php

class Error {

	public static function display(Exception $exception) {
		$html = array();
		$html[] = '<!DOCTYPE html>';
		$html[] = '<html>';
		$html[] = '<head>';
		$html[] = '<title>Tikori5 Critical Error</title>';
		$html[] = '<style>';
		$html[] = 'body{margin: 10px auto; max-width: 800px; font-size: 14px; line-height: 1.8em; background-color: blue; color: white; text-align: center;}';
		$html[] = 'body, pre {font-family: \'Consolas\', monospace;}';
		$html[] = 'span, h1 {color: blue; background-color: white; padding: 1px;}';
		$html[] = 'h1 {display: inline-block; padding: 10px;}';
		$html[] = '.stopka {color: steelblue; font-size: 12px; font-family: monospace;}';
		$html[] = '.l {text-align: left;}';
		$html[] = '.code {text-align: left; font-size: 11px; background-color: #333; overflow-x: scroll;}';
		$html[] = '.code, .code pre {line-height: 15px; margin: 0;}';
		$html[] = '.num {width: 40px; background-color: black; color: #888; display: inline-block; text-align: right; padding-right: 3px;}';
		$html[] = '.err {color: black; background-color: gold; padding: 0px;}';
		$html[] = '</style>';
		$html[] = '</head>';
		$html[] = '<body>';
		$html[] = '<h1>Tikori5 Critical Error</h1>';
		$html[] = '<p><strong>A problem has been detected and Tikori5 framework was unable to complete request, because:</strong></p>';
		$html[] = '<p>' . $exception->getMessage();
		if (Core::app()->getMode() < Core::MODE_PROD) {
			if (Core::app()->cfg('env') !== null) {
				$e = Core::app()->cfg('env');
				$html[] = '<br/>Requested <span>' . $e['REQUEST_METHOD'] . '</span>: ' . $e['PATH_INFO'];
			}

//			if ($file = $exception->getFile()) {
			$html[] = '<div class="l">';
			$html[] = 'Technical information:<br/>';
			$html[] = '<u>File:</u><br/>';
			$html[] = $exception->getFile() . ':' . $exception->getLine();
			$html[] = '<br/><u>Stack:</u>';
			$html[] = '<br/>' . nl2br($exception->getTraceAsString());
			$html[] = '</div>';

			if ($file = @file($exception->getFile())) {
				$html[] = '<br/>';
				$html [] = '<div class="code"><pre>';
				$code = array();
				for ($i = max(0, $exception->getLine() - 11); $i < min(count($file) - 1, $exception->getLine() + 10); $i++) {
//					if (!preg_match('/[a-z\/]/i', $file[$i]))
//						continue;

					$code[] = '<span class="num">' . sprintf('%05d', $i+1) . ':</span>';
					if ($i == $exception->getLine() - 1) {
						$code[] = '<span class="err">';
					}
					$code[] = htmlspecialchars($file[$i]);
					if ($i == $exception->getLine() - 1) {
						$code[] = '</span>';
					}
				}
				$html [] = implode('', $code);
				$html[] = '</pre></div>';
			}
//			}
		}
		$html[] = '</p>';
		$html[] = '<div class="stopka">';
		$html[] = '&copy; 2003 - ' . date('Y') . ' gnysek.pl &bull; ' . date('d/m/Y H:i:s');
		$html[] = '</div>';
		$html[] = '</body>';
		$html[] = '</html>';

		echo implode('', $html);
	}

}
