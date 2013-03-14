<?php

class XTikori
{

    const MODE_DEBUG = -1;
    const MODE_DEV = 0;
    const MODE_PROD = 1;

    public function __construct()
    {
        $this->request = new TikoriRequest();
        $this->response = new TikoriResponse();
        $this->router = new TikoriRouter($this->request->getResourceUri());

        $this->view = new TikoriView();
    }

    public function render($template, $data, $status = null)
    {
        if (!is_null($status)) {
            $this->response->status($status);
        }
        $this->view->setTemplatesDirectory($this->config('templates.path'));
        $this->view->appendData($data);
        $this->view->display($template);
    }

    public function getMode()
    {
        if (!isset($this->mode)) {
            if (isset($_ENV['TCORE_MODE'])) {
                $this->mode = $_ENV['TCORE_MODE'];
            } else {
                $envMode = getenv('TCORE_MODE');
                if ($envMode !== false) {
                    $this->mode = $envMode;
                } else {
                    $this->mode = self::MODE_DEV; //$this->config('mode');
                }
            }
        }

        return $this->mode;
    }

    /**
     * Get the absolute path to this Slim application's root directory
     *
     * This method returns the absolute path to the Slim application's
     * directory. If the Slim application is installed in a public-accessible
     * sub-directory, the sub-directory path will be included. This method
     * will always return an absolute path WITH a trailing slash.
     *
     * @return string
     */
    public function root()
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . rtrim($this->request->getRootUri(), '/') . '/';
    }

    /**
     * Clean current output buffer
     */
    protected function cleanBuffer()
    {
        if (ob_get_level() !== 0) {
            ob_clean();
        }
    }

    public function halt($status = 404, $message = '')
    {
        $this->cleanBuffer();
        $this->response->status($status);
        $this->response->body($message);
        exit;
//        $this->stop();
    }

    public function redirect($url, $status = 302)
    {
        $this->response->redirect($url, $status);
        $this->halt($status);
    }

    public function run()
    {
        //set_error_handler(array(,));

        list($status, $header, $body) = $this->response->finalize();

        //Send headers
//		if (headers_sent() === false) {
//			//Send status
//			if (strpos(PHP_SAPI, 'cgi') === 0) {
//				header(sprintf('Status: %s', TikoriResponse::getMessageForCode($status)));
//			} else {
//				header(sprintf('HTTP/%s %s', $this->config('http.version'), TikoriResponse::getMessageForCode($status)));
//			}
//
//			//Send headers
//			foreach ($header as $name => $value) {
//				$hValues = explode("\n", $value);
//				foreach ($hValues as $hVal) {
//					header("$name: $hVal", false);
//				}
//			}
//		}

        echo $body;

        //restore_error_handler();
    }

}
