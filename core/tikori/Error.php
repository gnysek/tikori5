<?php

/**
 * This class handles exceptions and errors, and displays them with nice design
 *
 * @author Piotr Gnys <gnysek@gnysek.pl>
 */
class Error
{

    /* register all handlers */
    public static function registerErrors()
    {
        set_exception_handler(array('Error', 'exch'));
        set_error_handler(array('Error', 'errh'), E_ALL);
    }

    /**
     * Handler for set_exception_handler
     *
     * @param Exception $exception catched Exception
     */
    public static function exch(Exception $exception)
    {
        echo Error::display($exception);
    }

    /**
     * Handler for set_error_handler
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @param mixed  $errcontext
     */
    public static function errh($errno, $errstr, $errfile, $errline, $errcontext)
    {
        //echo Error::display(new Exception($errstr, $errno), array('file' => $errfile, 'line' => $errline));
        echo Error::display(new ErrorException($errstr, 0, $errno, $errfile, $errline), true);
    }

    /**
     * Displays error using error.fatal.php view
     *
     * @param Exception $exception
     * @param bool      $isErrorHandler if it's called by error handler we need to skip $excetpion->getFile to avoid duplicates on trace
     */
    public static function display(Exception $exception, $isErrorHandler = false)
    {

        for ($i = 0, $obLevel = ob_get_level(); $i < $obLevel; ++$i) {
            ob_end_clean();
        }

        $view = new Controller();
        $e = Core::app()->cfg('env');

        $files = array();

        foreach (array_reverse($exception->getTrace()) as $trace) {
            if (isset($trace['line'], $trace['file'])) {
                $files[] = self::getFile($trace['file'], $trace['line'], $trace);
            }
        }

        // TODO: check that its needed since erh have ErrorException used
        if ($isErrorHandler === false) {
            $files[] = self::getFile(
                $exception->getFile(), $exception->getLine(), array('class' => 'Error::display()')
            );
        }

        $body = $view->renderPartial(
            'error.fatal', array(
                                'message'   => $exception->getMessage(),
                                'file'      => $exception->getFile(),
                                'line'      => $exception->getLine(),
                                'reqMethod' => (empty($e[Request::REQUEST_METHOD])) ? '' : $e[Request::REQUEST_METHOD],
                                'reqPath'   => (empty($e[Request::PATH_INFO])) ? '' : $e[Request::PATH_INFO],
                                'files'     => $files,
                           ), true
        );

        echo $body;
        exit;
    }

    /**
     * Prints error code from file
     *
     * @param string $filename
     * @param int    $line
     * @param array  $trace
     *
     * @return string HTML with code
     */
    private static function getFile($filename, $line, $trace = array())
    {
        $html = array();

        if ($file = @file($filename)) {

            $dispName = str_replace(Core::app()->appDir . DIRECTORY_SEPARATOR, '', $filename);
            $dispName = str_replace('\\', '/', $dispName);

            $index
                = /* preg_replace('#[^a-z]#i', '', str_replace('.php', '', $dispName)) . $line . */
                uniqid();

            $html[] = '<p class="pink">' . $dispName . ':' . $line . '</p>' . PHP_EOL;
            $html[] = '<p><code class="prettyprint lang-php highlight" onclick="$(\'#' . $index . '\').toggle();">';
            $html[] = '<span class="num nocode">' . sprintf('%05d', $line) . '</span>';
            $html[] = ltrim(substr($file[$line - 1], 0, 85)) . '<br/>';
            $html[] = '<span class="num nocode">   &raquo;</span>';
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
                        if (is_string($arg) or is_numeric($arg)) {
                            if (is_string($arg)) {
                                if (strlen($arg) > 15) {
                                    $arg = substr($arg, 0, 15) . '&hellip;';
                                }
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

            $html[] = PHP_EOL . '<div class="code hidden" id="' . $index . '"><pre class="prettyprint lang-php">';
            $code = array();
            for ($i = max(0, $line - 11); $i < min(count($file), $line + 10); $i++) {
//					if (!preg_match('/[a-z\/]/i', $file[$i]))
//						continue;

                $code[] = '<div class="line">';
                if ($i == $line - 1) {
                    $code[] = '<div class="err">';
//				} else if ($i != $line) {
//					$code[] = PHP_EOL;
                }
                $code[] = '<span class="num nocode">' . sprintf('%05d', $i + 1) . '</span>';
                $code[] = htmlspecialchars(str_replace(array("\n", "\r", "\r\n"), '', $file[$i]));
                if ($i == $line - 1) {
                    $code[] = '</div>';
                }
                $code[] = '</div>';
            }
            $html[] = implode('', $code);
            $html[] = '</pre></div>';
        }

        return implode('', $html);
    }

}
