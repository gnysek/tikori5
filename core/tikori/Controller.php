<?php

/**
 * Class Controller
 *
 * @property Request $request
 * @property string  $pageTitle
 */
class Controller extends ControllerView
{

    public $layout = '//layout.default';
    public $controller = 'default';
    public $action = 'default';
    public $params = array();
    public $area = null;
    public $scope = null;
    public $checkPermissions = false;
    public $pageTitle = '';
    /**
     * @var Request
     */
    public $request = NULL;

    public function __construct($area = NULL, $scope = NULL)
    {
        Profiler::addLog('&bull; New controller <code>' . get_called_class() . '</code> Created');
        $this->area = $area;
        $this->scope = $scope;
        if ($this->pageTitle === '') { // only if not changed in child class
            $this->pageTitle = Core::app()->cfg('appName');
        }
        $this->afterConstruct();
        // if HMVC will come some day, this need to be changed
        $this->request = Core::app()->request;

        parent::__construct();
    }

    public function afterConstruct()
    {
        return true;
    }

    public function __get($name)
    {
        return null;
    }

    /*public function run($actionID) {
        if (($action = $this->createAction($actionID)) !== null) {
//            if ($this->beforeAction()) {
                $this->runActionNew($action);
//                $this->afterAction();
//            }
        } else {
            throw new CHttpException(404, 'Action not found');
        }
    }*/

    /**
     * @throws Exception
     */
    public function unknownAction()
    {
        $this->httpStatusAction(404);
    }

    /**
     * @param $action
     */
    public function runActionNew($action)
    {
        if ($this->_actionBefore()) {
            if ($action->runWithParams()) {
                $this->_afterAction();
            } else {
                $this->invalidActionParams();
            }
        }
    }

//    public function runAction($controller = NULL, $action = NULL)
//    {
//        Profiler::addLog(
//            '-> Running Action: <kbd>' . $this->getControllerClassName($controller) . '/' . $action . '</kbd>'
//        );
//        if (get_called_class() == $controller) {
//            $this->run(Core::app()->route, $action);
//        } else {
//            $class = $this->getControllerClassName($controller);
//            $c = new $class($this->area);
//            $c->run(Core::app()->route, $action);
//        }
//    }

    /**
     * @param string $area
     * @throws Exception
     */
    public static function forward404($area = '')
    {
        self::_forwardHttpCode($area, 404);
    }

    /**
     * @param string $area
     * @throws Exception
     */
    public static function forward401($area = '')
    {
        self::_forwardHttpCode($area, 104);
    }

    /**
     * @param $area
     * @param $code
     * @throws Exception
     */
    protected static function _forwardHttpCode($area, $code)
    {
        $c = new Controller($area);
        $c->httpStatusAction($code);
    }

    /**
     * @return bool
     */
    protected function _beforeRun()
    {
        return true;
    }

    /**
     * @param $route
     * @param null $action
     * @throws Exception
     */
    public function run($route, $action = NULL)
    {
        if ($route instanceof Route) {
            $this->area = $route->area;
            $this->action = ($action === NULL) ? $route->action : $action;
            $this->controller = $route->controller;
            $this->params = $route->params;
        } else {
            if ($action !== NULL) {
                $this->action = $action;
            } else {
                $this->action = 'default';
            }
        }

        if (empty($this->action) /*or !method_exists($this, $this->getActionMethodName())*/) {
            $this->action = 'default';
        }

        if (!$this->_beforeRun()) {
            return;
        }

        if (!method_exists($this, $this->getActionMethodName())) {

            Profiler::addLog('No method found for <code>' . $this->getActionMethodName() . '</code>');

            $this->_actionBefore();
            if (Core::app()->response->status() == 200) {

                if (method_exists($this, 'defaultAction')) {
                    $this->defaultAction();
                } else {
                    $this->unknownAction();
                }

            } else {
                // seems that it already returned something
            }

            $this->_afterAction();
        } else {
            Profiler::addLog(
                'Calling controller: <kbd>' . $this->getControllerClassName() . '::' . $this->getActionMethodName() . '</kbd>'
            );

            // check params
            try {
                $finalParams = array();
                $method = new ReflectionMethod($this, $this->getActionMethodName());

//                if ($method->getNumberOfRequiredParameters() > 0) {
                if ($method->getNumberOfParameters() > 0) {
                    //						var_dump($method->getNumberOfRequiredParameters());

                    foreach ($method->getParameters() as $paramObject) {
                        /* @var $paramObject ReflectionParameter */

                        if ($paramObject->isOptional() === false and !array_key_exists($paramObject->name, $this->params)) {
                            //throw new RouteNotFoundException('Not enough arguments or wrong argument name [' . $paramObject->name . ']');
                            throw new Exception(
                                'Not enough arguments for method ' . $this->getActionMethodName()
                                . '() or wrong argument name, (expected [' . $paramObject->name . ']) - should be one of <code>'
                                . implode(', ', array_keys($this->params)) . '</code>'
                            );
                        }

                        $finalParams[] = (!array_key_exists($paramObject->name, $this->params))
                            ? $paramObject->getDefaultValue()
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
                if ($this->_actionBefore()) {
                    call_user_func_array(array($this, $this->getActionMethodName()), $finalParams);
                    $this->_afterAction();
                } else {
                    if (Core::app()->response->status() == 200) {
                        ob_get_clean();
                        $this->httpStatusAction(404);
                    }
                }
                // end buffer
                $response = ob_get_clean();

                Profiler::addLog('Setting reponse using last controller action');
                Core::app()->response->body($response);
            } catch (DbError $e) {
                //TODO: shouldn't be like that...
                ob_get_clean();
                \Tikori\Error::exch($e);
            } catch (Exception $e) {
                if (Core::app()->getMode() !== Core::MODE_PROD) {
                    \Tikori\Error::exch($e);
                }
                //				if (($this instanceof ErrorController)==false)
                Profiler::addLog('Exception when performing action: ' . $e->getMessage());
                ob_get_clean();
                $this->unknownAction();
            }
        }
        //		} else {
        //			throw new Exception('$route need to be instance of Route class!');
        //		}
    }

    /**
     * @param null $controller
     * @param null $arena
     * @param string $suffix
     * @return string
     */
    public static function getControllerClassName($controller = NULL, $arena = NULL, $suffix = 'Controller')
    {
        $strict = 'this'; // fix for E_STRICT notice
        if (isset($$strict)) {
            if ($controller === NULL) {
                $controller = $$strict->controller;
            }
        }

        return (!empty($arena) ? ucfirst($arena) . '_' : '') . ucfirst(strtolower(($controller))) . $suffix;
    }

    /**
     * @param null $action
     * @param string $suffix
     * @return string
     */
    public function getActionMethodName($action = NULL, $suffix = 'Action')
    {
        if (isset($this)) {
            if ($action === NULL) {
                $action = $this->action;
            }
        } else {
            if ($action === NULL) {
                $action = 'default';
            }
        }

        return strtolower($action) . $suffix;
    }

    /**
     * @return bool
     */
    protected function _actionBefore()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function _afterAction()
    {
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function checkPermissions()
    {
        //		var_dump($this->checkPermissions);
        if ($this->checkPermissions) {
            if (Core::app()->session === NULL) {
                //if (Core::registry('session') == false) {
                throw new Exception('Session module need to be activated in config if you want to check Permissions');
            }
        }

        return true;
    }

    /**
     * @param string $controller
     */
    public function setController($controller = '')
    {
        $this->controller = $controller;
    }

    /**
     * @param string $action
     */
    public function setAction($action = '')
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getControllerFullName()
    {
        return ucfirst($this->controller) . 'Controller';
    }

    /**
     * @param $params
     */
    public function setParams($params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @param int $status
     * @throws Exception
     * @return null
     */
    public function httpStatusAction($status = 404)
    {
        ob_get_clean();
        $this->pageTitle = Core::app()->cfg('appName');
        Core::app()->response->status($status);
        $this->render('http404', array('status' => $status, 'message' => Response::getMessageForCode($status)));

        return null;
    }

    /**
     * @param string|array $where
     * @throws Exception
     */
    public function redirect($where = '/')
    {
        $where = Html::url($where);
        //TODO: what if before redirect text was send?
        Core::app()->response->header('Location', $where);
    }

    /**
     * @param string|array $where
     * @throws Exception
     */
    public function redirectStatus($where = '/', $status = 301)
    {
        $where = Html::url($where);
        //TODO: what if before redirect text was send?
        Core::app()->response->header('HTTP/1.1 301 Moved Permanently');
        Core::app()->response->header('Location', $where);
    }

    //	public function defaultAction() {
    //		throw new RouteNotFoundException('Unknown action');
    //	}
}
