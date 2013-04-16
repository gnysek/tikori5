<?php

/**
 * Class Controller
 *
 * @property Request $request
 * @property string  $pageTitle
 */
class Controller
{

    public $layout = '//layout.default';
    public $controller = 'default';
    public $action = 'default';
    public $params = array();
    public $area = '';
    public $checkPermissions = false;
    public $pageTitle = '';
    /**
     * @var Request
     */
    public $request = null;

    public function __construct($area = null)
    {
        Profiler::addLog('&bull; New controller <code> ' . get_called_class() . '</code> Created');
        $this->area = $area;
        $this->pageTitle = Core::app()->cfg('appName');
        $this->afterConstruct();
        // if HMVC will come some day, this need to be changed
        $this->request = Core::app()->request;
    }

    public function afterConstruct()
    {
        return true;
    }

    public function runAction($controller = null, $action = null)
    {
        Profiler::addLog(
            '-> Running Action: <tt>' . $this->getControllerClassName($controller) . '/' . $action . '</tt>'
        );
        if (get_called_class() == $controller) {
            $this->run(Core::app()->route, $action);
        } else {
            $class = $this->getControllerClassName($controller);
            $c = new $class($this->area);
            $c->run(Core::app()->route, $action);
        }
    }

    public static function forward404($area = '')
    {
        $c = new Controller($area);
        $c->httpStatusAction(404);
        //$c->runAction('', 'httpStatus');
    }

    public static function forward401($area = '')
    {
        $c = new Controller($area);
        $c->httpStatusAction(401);
    }

    protected function _beforeRun()
    {
        return true;
    }

    public function run($route, $action = null)
    {
        if ($route instanceof Route) {
            $this->area = $route->area;
            $this->action = ($action === null) ? $route->action : $action;
            $this->controller = $route->controller;
            $this->params = $route->params;
        } else {
            if ($action !== null) {
                $this->action = $action;
            }
        }

        if (empty($this->action) or !method_exists($this, $this->getActionMethodName())) {
            $this->action = 'default';
        }

        if (!$this->_beforeRun()) {
            return;
        }

        if (!method_exists($this, $this->getActionMethodName())) {
            Profiler::addLog('No method found for <code>' . $this->getActionMethodName() . '</code>');
            $this->forward404($this->area);
        } else {
            Profiler::addLog(
                'Calling controller: <tt>' . $this->getControllerClassName() . '::' . $this->getActionMethodName()
                    . '</tt>'
            );

            // check params
            try {
                $finalParams = array();
                $reflection = new ReflectionClass($this);
                $method = $reflection->getMethod($this->getActionMethodName());
                /* @var $method ReflectionMethod */

//                if ($method->getNumberOfRequiredParameters() > 0) {
                if ($method->getNumberOfParameters() > 0) {
                    //						var_dump($method->getNumberOfRequiredParameters());

                    foreach ($method->getParameters() as $paramObject) {
                        /* @var $paramObject ReflectionParameter */

                        if ($paramObject->isOptional() === false and empty($this->params[$paramObject->name])) {
                            //throw new RouteNotFoundException('Not enough arguments or wrong argument name [' . $paramObject->name . ']');
                            throw new ErrorException(
                                'Not enough arguments on method ' . $this->getActionMethodName()
                                    . '() or wrong argument name [' . $paramObject->name . '] - should be one of <code>'
                                    . implode(', ', array_keys($this->params)) . '</code>'
                            );
                        }

                        $finalParams[] = (empty($this->params[$paramObject->name])) ? null
                            : $this->params[$paramObject->name];
                    }
                }
            } catch (Exception $e) {
                throw new Exception('Cannot use Reflection on Controller: ' . $e->getMessage());
            }

            $this->checkPermissions();

            // finally perform action
            try {
                ob_start();
                // buffer
                $this->beforeAction();
                call_user_func_array(array($this, $this->getActionMethodName()), $finalParams);
                $this->afterAction();
                // end buffer
                $response = ob_get_clean();

                Profiler::addLog('Setting reponse using last controller action');
                Core::app()->response->body($response);
            } catch (DbError $e) {
                //TODO: shouldn't be like that...
                Error::exch($e);
            } catch (Exception $e) {
                //				if (($this instanceof ErrorController)==false)
                Profiler::addLog('Exception' . $e->getMessage());
                $this->forward404();
            }
        }
        //		} else {
        //			throw new Exception('$route need to be instance of Route class!');
        //		}
    }

    public static function getControllerClassName($controller = null, $arena = null, $suffix = 'Controller')
    {
        $strict = 'this'; // fix for E_STRICT notice
        if (isset($$strict)) {
            if ($controller === null) {
                $controller = $$strict->controller;
            }
        }

        return (!empty($arena) ? ucfirst($arena) . '_' : '') . ucfirst(strtolower(($controller))) . $suffix;
    }

    public function getActionMethodName($action = null, $suffix = 'Action')
    {
        if (isset($this)) {
            if ($action === null) {
                $action = $this->action;
            }
        } else {
            if ($action === null) {
                $action = 'default';
            }
        }

        return strtolower($action) . $suffix;
    }

    public function beforeAction()
    {
        return true;
    }

    public function afterAction()
    {
        return true;
    }

    public function checkPermissions()
    {
        //		var_dump($this->checkPermissions);
        if ($this->checkPermissions) {
            if (Core::app()->session === null) {
                //if (Core::registry('session') == false) {
                throw new Exception('Session module need to be activated in config if you want to check Permissions');
            }
        }

        return true;
    }

    public function setController($controller = '')
    {
        $this->controller = $controller;
    }

    public function setAction($action = '')
    {
        $this->action = $action;
    }

    public function getControllerFullName()
    {
        return ucfirst($this->controller) . 'Controller';
    }

    public function setParams($params)
    {
        $this->params = array_merge($this->params, $params);
    }

    public function httpStatusAction($status = 404)
    {
        Core::app()->response->status($status);
        $this->render('http404', array('status' => $status, 'message' => Response::getMessageForCode($status)));
    }

    public function redirect($where = '/')
    {
        $where = HTML::url($where);
        //TODO: what if before redirect text was send?
        Core::app()->response->header('Location', $where);
    }

    //	public function defaultAction() {
    //		throw new RouteNotFoundException('Unknown action');
    //	}

    public function render($file = null, $data = null, $return = false)
    {
        //TODO: no error when file not found?

        if (!empty($file) && $this->viewExists($file)) {
            $out = $this->renderPartial($file, $data);
        } else {
            $out = (string)$data;
        }

        $out = $this->renderPartial($this->layout, array('content' => $out));

        if ($return) {
            return $out;
        } else {
            echo $out;
        }
    }

    public function renderPartial($file, $data = null, $return = true)
    {
        if ($filename = $this->_findViewFile($file)) {
            return $this->renderInternal($filename, $data, $return);
        } else {
            throw new Exception('View ' . $file . ' not found.');
        }
    }

    public function renderInternal($_fileNC, $_dataNC = null, $_returnNC = false)
    {
        if (is_array($_dataNC)) {
            extract($_dataNC, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_dataNC;
        }

        if ($_returnNC) {
            ob_start();
            ob_implicit_flush(false);
            Profiler::addLog('Rendering <tt>' . str_replace(Core::app()->appDir, '', $_fileNC) . '</tt>');
            require($_fileNC);
            return ob_get_clean();
        } else {
            require $_fileNC;
        }
    }

    public function viewExists($view)
    {
        return ($this->_findViewFile($view) !== false);
    }

    protected function _findViewFile($file)
    {
        $paths = array();

        if (substr($file, 0, 2) != '//') {
            $paths[] = Core::app()->appDir . '/views/' . $this->controller . '/';
            $paths[] = Core::app()->coreDir . '/views/' . $this->controller . '/';

            $modules = Core::app()->cfg('modules');
            if (!empty($modules)) {
                //TODO: find a better way to get module name...
                $reflection = new ReflectionClass($this);
                $currentModule = strtolower(
                    preg_replace('#(?:.*?)modules(?:\\\|/)([a-zA-Z0-9_]*)(?:.*)#i', '$1', $reflection->getFilename())
                );

                if (!empty($currentModule)) {
                    foreach ($modules as $module => $config) {
                        $module = strtolower($module);
                        if ($module == $currentModule) {
                            $paths[]
                                = Core::app()->appDir . '/modules/' . $module . '/views/';
                            /* strtolower($this->controller) . */
                            $paths[]
                                = Core::app()->coreDir . '/modules/' . $module . '/views/';
                            /* strtolower($this->controller) . */
                        }
                    }
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

    public function widget($class, $properties = array(), $captureOutput = false)
    {
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

    private function _createWidget($class, $properties)
    {
        $className = ucfirst($class) . 'Widget';
        $widget = new $className;
        $widget->setupProperties($properties);
        $widget->init();
        return $widget;
    }

}
