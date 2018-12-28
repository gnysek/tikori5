<?php

/**
 * This class handles exceptions and errors, and displays them with nice design
 *
 * @author Piotr Gnys <gnysek@gnysek.pl>
 */
namespace Tikori;

class Error
{
    public static $renderStarted = false;
    public static $enableStrict = true;

    /* register all handlers */
    public static function registerErrors()
    {
        set_exception_handler(array('\Tikori\Error', 'exch'));
        set_error_handler(array('\Tikori\Error', 'errh'), E_ALL);
    }

    public static function shutdown_handler() {
        if ($error = error_get_last() AND in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))) {
            if (ob_get_level()) {
                ob_clean();
                ob_end_clean();
            }

            $exception = new \ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);

            if (function_exists('xdebug_get_function_stack')) {
                $stack = array();
                foreach (array_slice(array_reverse(xdebug_get_function_stack()), 2, -1) as $row) {
                    $frame = array(
                        'file' => $row['file'],
                        'line' => $row['line'],
                        'function' => isset($row['function']) ? preg_replace('/(.*?):(.*)/i','$1',$row['function']) : '*unknown*',
                        'args' => array(),
                    );
                    if (!empty($row['class'])) {
                        $frame['type'] = isset($row['type']) && $row['type'] === 'dynamic' ? '->' : '::';
                        $frame['class'] = $row['class'];
                    }
                    $stack[] = $frame;
                }
                $ref = new \ReflectionProperty('\Exception', 'trace');
                $ref->setAccessible(TRUE);
                $ref->setValue($exception, $stack);
            }
            self::exch($exception);

            exit(1); // prevent infinity-loop
        }
    }

    /**
     * Handler for set_exception_handler
     *
     * @param \Exception|\Throwable $exception catched Exception
     * @throws \Exception
     */
    public static function exch($exception)
    {
        echo self::display($exception);
    }

    /**
     * Handler for set_error_handler
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param mixed $errcontext
     * @return bool
     * @throws \Exception
     */
    public static function errh($errno, $errstr, $errfile, $errline, $errcontext)
    {
        //echo Error::display(new Exception($errstr, $errno), array('file' => $errfile, 'line' => $errline));
        if (in_array($errno, array(E_WARNING))) {
            self::log('[Muted Warning] ' . $errfile . ':' . $errline . ':: ' . $errstr);
            return false;
        }
        if (self::$enableStrict === false and in_array($errno, array(E_NOTICE, E_STRICT))) {
            return false;
        }
        echo self::display(new \ErrorException($errstr, $errno, 1, $errfile, $errline), true);
        return true;
    }

    public static function log($data, $file = 'system.log')
    {
        if (!file_exists(TIKORI_ROOT . '/log')) {
            mkdir(TIKORI_ROOT . '/log', 0777, true);
        }

        if (!file_exists(TIKORI_ROOT . '/log/' . $file)) {
            file_put_contents(TIKORI_ROOT . '/log/' . $file, '');
        }

        $url = ' [ url not known ]';

        if (isset($_SERVER['HTTP_HOST'])) {
            $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        $f = fopen(TIKORI_ROOT . '/log/' . $file, 'a+');
        fwrite($f, str_repeat('-', 80) . PHP_EOL);
        fwrite($f, date('d.m.Y H:i:s') . ': ' . PHP_EOL . $url . PHP_EOL . var_export($data, true) . PHP_EOL);
        fclose($f);
    }

    /**
     * Displays error using error.fatal.php view
     *
     * @param \Exception|\Throwable $exception
     * @param bool $isErrorHandler if it's called by error handler we need to skip $excetpion->getFile to avoid duplicates on trace
     * @param bool $dontExit
     *
     * @return string
     * @throws \Exception
     */
    public static function display($exception, $isErrorHandler = false, $dontExit = false)
    {

        for ($i = 0, $obLevel = ob_get_level(); $i < $obLevel; ++$i) {
            ob_end_clean();
        }

        self::log($exception->getFile() . ':' . $exception->getLine() . ':: ' . $exception->getMessage());

        $errors = array();

        $current = $exception;

        do {
            $files = array();

            $lastTrace = null;

            foreach (array_reverse($current->getTrace()) as $trace) {
                if (isset($trace['line'], $trace['file'])) {
                    #$traced = true;

                    $info = array();
                    foreach (array('function', 'class', 'type') as $key) {
                        $info[$key] = (empty($trace[$key])) ? '' : $trace[$key];
                    }
                    $info['args'] = (empty($trace['args'])) ? 0 : count($trace['args']);

                    $files[] = array(
                        'file' => str_replace(TIKORI_ROOT, '...', $trace['file']),
                        'line' => $trace['line'],
                        'info' => $info,
                        'html' => self::getFile($trace['file'], $trace['line'], $trace),
                    );

                    $lastTrace = $trace['file'] . $trace['line'];
                }
            }

            if ($lastTrace != ($exception->getFile() . $exception->getLine())) {
                $files[] = array(
                    'file' => str_replace(TIKORI_ROOT, '...', $exception->getFile()),
                    'line' => $exception->getLine(),
                    'info' => array(),
                    'html' => self::getFile($exception->getFile(), $exception->getLine(), $exception->getTrace()),
                );
            }

            $errors[] = array(
                'message' => $current->getMessage(),
                'id' => (count($errors)),
                'files' => array_reverse($files),//&$files,
                'file' => str_replace(TIKORI_ROOT, '...', $exception->getFile()),
                'line' => $exception->getLine(),
            );

        } while ($current = $current->getPrevious());

        $view = new \Controller();
        $e = \Core::app()->cfg('request');

        // TODO: check that its needed since erh have ErrorException used
        if ($isErrorHandler === false) {
            /*$files[] = array(
                'file' => str_replace(TIKORI_ROOT, '...', $exception->getFile()),
                'line' => $exception->getLine(),
                'info' => '',
                'html' => self::getFile($exception->getFile(), $exception->getLine(), array('class' => 'self::display()')
            ));*/
        }

        //$files = array_reverse($files);

        $code = '<unknown>';
        $codeInt = $exception->getCode();
        $codesArray = array(
            E_ERROR => 'E_ERROR / Fatal error', // 1
            E_WARNING => 'E_WARNING / Warning', // 2
            E_PARSE => 'E_PARSE / Parse Error', // 4
            E_NOTICE => 'E_NOTICE / Notice', // 8
            E_CORE_ERROR => 'Fatal core startup error', // 16
            E_CORE_WARNING => 'Core startup warning', // 32
            E_COMPILE_ERROR => 'Compiler error', // 64
            E_COMPILE_WARNING => 'Compile warning', // 128
            E_USER_ERROR => 'E_ERROR', //256
            E_USER_WARNING => 'Warning (user)', // 512
            E_USER_NOTICE => 'E_USER_NOTICE', // 1024
            E_STRICT => 'E_STRICT', // 2048
            E_RECOVERABLE_ERROR => 'Recoverable', // 4096
            E_DEPRECATED => 'E_DEPRECEATD', // 8192
            E_USER_DEPRECATED => 'E_DEPRECEATD', // 16384
            #E_ALL => 'All ?', // 32767
        );

        if (isset($codesArray[$codeInt])) {
            $code = $codesArray[$codeInt];
        }

        if (self::$renderStarted == false) {
            self::$renderStarted = true;

            $body = $view->renderPartial(
                (\Core::app()->getMode() == \Core::MODE_PROD) ? 'error.fatal' : 'core.exception',
                array(
                    'errorType' => $code,
                    'errorId' => $codeInt,
                    'message' => $exception->getMessage(),
                    'errors' => array_reverse($errors),
                    #'messages'  => $messages,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'reqMethod' => (empty($e[\Request::REQUEST_METHOD])) ? '' : $e[\Request::REQUEST_METHOD],
                    'reqPath' => (empty($e[\Request::PATH_INFO])) ? '' : $e[\Request::PATH_INFO],
                    #'files'     => $files,
                    'view' => $view,
                ), true
            );
        } else {
            if (\Core::app()->getMode() == \Core::MODE_PROD) {
                $body = '<p>There was fatal error during rendering page, sorry.</p>';
            } else {
                $body = '<p>There was an error and probably it occurs also when rendering error page:</p>';
                $body .= ' <b>' . $exception->getMessage() . '</b>';
                $body .= ' in <code>' . $exception->getFile() . '</code>';
                $body .= ' on line ' . $exception->getLine();
            }
        }

        if ($dontExit === true) {
            return $body;
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 500 ' . \Response::$messages['500']);
            }
            //var_dump($exception->getPrevious());
            echo $body;
            exit;
        }
    }

    /**
     * Prints error code from file
     *
     * @param string $filename
     * @param int $line
     * @param array $trace
     *
     * @return string HTML with code
     */
    private static function getFile($filename, $line, $trace = array())
    {
        $html = array();

        if ($file = @file($filename)) {

            $dispName = str_replace(\Core::app()->appDir . DIRECTORY_SEPARATOR, '', $filename);
            $dispName = str_replace('\\', '/', $dispName);

            $index
                = /* preg_replace('#[^a-z]#i', '', str_replace('.php', '', $dispName)) . $line . */
                uniqid();

            $html[] = '<p class="pink">' . $dispName . ':' . $line . '</p>' . PHP_EOL;
            $html[] = '<p><code class="prettyprint lang-php highlight linenums:' . $line . '">'; #onclick="$(\'#' . $index . '\').toggle();"
            #$html[] = '<span class="num nocode">' . sprintf('%04d', $line) . '.</span>';
            $html[] = ltrim(self::shortify($file[min(count($file), $line) - 1])) . '<br/>';
            #$html[] = '<span class="num nocode">   &raquo;</span>';
            if (!empty($trace['class'])) {
                $html[] = $trace['class'];
            }
            if (!empty($trace['type'])) {
                $html[] = $trace['type'];
            }
            if (!empty($trace['function'])) {
                $html[] = $trace['function'] . '(';
                if (!empty($trace['args'])) {
                    $args = array();
                    foreach ($trace['args'] as $arg) {
                        /*if (is_string($arg) or is_numeric($arg)) {
                            if (is_string($arg)) {
                                if (strlen($arg) > 15) {
                                    $arg = substr($arg, 0, 15) . '&hellip;';
                                }
                                $arg = '\'' . $arg . '\'';
                            }
                            $args[] = $arg;
                        } else*/
                        {
                            $args[] = gettype($arg);
                        }
                    }
                    $html[] = implode(', ', $args);
                }
                $html[] = ')';
            }
            $html[] = '</code></p>';

            $html[] = PHP_EOL . '<div id="' . $index . '"><code class="prettyprint lang-php linenums:' . max(1, $line - 10) . '">';
            $code = array();
            $checkedAgainstComment = false;
            for ($i = max(0, $line - 11); $i < min(count($file), $line + 10); $i++) {
//					if (!preg_match('/[a-z\/]/i', $file[$i]))
//						continue;

                //$code[] = '<span class="line">';
                if ($i == $line - 1) {
                    $code[] = '<span class="err">';
//				} else if ($i != $line) {
//					$code[] = PHP_EOL;
                }
                //$code[] = '<span class="num nocode">' . sprintf('%04d', $i + 1) . '.</span>';

                $file[$i] = str_replace('    ', "\t", $file[$i]);
                $file[$i] = htmlspecialchars(str_replace(array("\n", "\r", "\r\n"), '', $file[$i]));
                $file[$i] = str_replace("\t", str_repeat('&nbsp;', 4), $file[$i]);

                if ($checkedAgainstComment === false) {
                    if (preg_match('/^(\s+)\* /i', $file[$i])) {
                        $file[$i] = preg_replace('/^(\s+)\* /i', ' /*', $file[$i], 1);
                    }
                    $checkedAgainstComment = true;
                }
                //$file[$i] = (str_replace(array("\n", "\r", "\r\n"), '', $file[$i]));

                $code[] = $file[$i];
                if ($i == $line - 1) {
                    $code[] = '</span>';
                }
                //$code[] = '</span>';
                $code[] = '<br/>';
            }
            $html[] = implode('', $code);
            $html[] = '</code></div>';
        }

        return implode('', $html);
    }

    private static function shortify($text)
    {
        if (strlen($text) > 85) {
            return htmlspecialchars(substr($text, 0, 85)) . '&hellip;';
        }

        return htmlspecialchars($text);
    }

}
