<?php
//use Core\User\NullSession;

/**
 * @property Request $request
 * @property Response $response
 * @property Route $route
 * @property int $mode           Core::MODE_XXX
 * @property DbAbstract $db
 * @property TCache|Cache $cache          Cache module
 * @property SessionModule|Session|AbstractSession $session        Session module
 * @property array $autoloadPaths  Array of autoload paths
 * @property Observer $observer
 * @property Cookie $cookie
 * @property ToolbarModule $toolbar
 */
class Tikori extends Application
{

    /**
     * @var Request
     */
    public $request = NULL;

    /**
     * @var Response
     */
    public $response = NULL;

    /**
     * @var Lang
     */
    public $lang = NULL;

    /**
     * @var Route
     */
    public $route = NULL;

    public function registerCoreModules()
    {
        $modules = array(
            'errorHandler' => array('class' => 'Error'),
            'session' => array('class' => 'TSession'),
            'user' => array('class' => 'TUser'),
            'cache' => array('class' => 'TCache'),
            'widgetFactory' => array('class' => 'TWidgetFactory'),
        );
        //TODO: enable
//        $this->setModules($modules);
    }

    public function getDatabaseDriver()
    {
        if ($this->cfg('db/type') != "") {
            if ($this->cfg('db/type') == 'mysqli') {
                return 'DbMySqli';
            }
        }
        return 'DbPDO';
    }

    /**
     * @param $config
     * @return bool
     * @throws Exception
     */
    public function run($config) {

        if (file_exists('.maintenance')) {
            // load languages
            $this->request = new Request();

            $this->lang = new Lang();
            //$this->lang->loadLanguages();

            $this->response = new Response();
            $this->response->status(503);
            $view = new TView();
            $view->renderPartial('error.503', array(), false);
            $this->response->send();
            exit;
        }

        // enable cache, we need that for config
        $this->setComponent('cache', new Cache());
        $regenerateAutloads = false;
        if ($this->cache->findCache('config-sum')) {
            // todo: load merged config
        }

        $b = Profiler::benchStart(\Profiler::BENCH_CAT_CORE, 'Getting autoload cache');
        Core::getAutoloadCache();
        Profiler::benchFinish($b);

        // route
        $b = Profiler::benchStart(\Profiler::BENCH_CAT_CORE, 'Route generation');
        Route::reconfigure();
        Profiler::benchFinish($b);

        //TODO: this should be moved to parent!
        //$this->observer = new Observer();

        $b = Profiler::benchStart(Profiler::BENCH_CAT_SQL, 'Database setup');
        if ($this->cfg('db', null) !== null) {
            $_db = $this->getDatabaseDriver();
            $db = new $_db();
            $this->setComponent('db', $db);
            unset($_db, $db);

            if ($this->cfg(DBConfig::CONFIG_FIELD, null) !== null) {
                $this->setComponent('dbconfig', new DBConfig());
            }
        }
        Profiler::benchFinish($b);

        // configure modules
        $b = Profiler::benchStart(Profiler::BENCH_CAT_CORE, 'Prepare modules');
        $modules = $this->cfg('modules');
        if (!empty($modules)) {
            foreach ($this->cfg('modules') as $module => $configPath) {

                if ($this->dbconfig) {
                    $this->dbconfig->renconfigure($module);
                }

                $bsub = Profiler::benchStart(Profiler::BENCH_CAT_CORE, 'Loading module ' . $module);
                $this->preloadModule($module, $configPath);
                Profiler::benchFinish($bsub);
            }
        }
        Profiler::benchFinish($b);

        $this->session = new Core\Common\NullSession();
        $this->user = new Core\Common\EmptyUser();

        // default routes
//		Route::set('tikori-admin', '<directory>(/<controller>(/<action>(/<id>)))(.html)', array('directory' => 'admin', 'id' => '.+'))
//			->defaults(array(
//				'controller' => 'admin',
//				'action' => 'index',
//			));
////		Route::set('tikori-default', '(<controller>(/<action>(/<id>)))(.html)')
        Route::set('tikori-default', '(<controller>(/<action>(/<tparams>)))', array('tparams' => '[a-zA-Z0-9_/]+'))
            ->defaults(
                array(
                    'controller' => ($this->cfg('default') !== NULL) ? $this->cfg('default') : 'default',
                    'action' => 'index',
                )
            );

        // request
        $this->request = new Request();
        Core::event(Request::EVENT_REQUEST_CREATED, null);

        $this->setComponent('cookie', new Cookie());
        Profiler::addLog('Request created');
        $this->response = new Response();
        Profiler::addLog('Response created');

        if ($this->request->isHardRefresh()) {
            //clear table schema
            $this->db->getSchema()->clearSchemaCache();
        }

        // load languages
        $this->lang = new Lang();
        //$this->lang->loadLanguages();

        // process route
        $b = Profiler::benchStart(\Profiler::BENCH_CAT_CORE, 'Route processing');
        $this->route = Route::process_uri($this->request->getRouterPath());
        Profiler::benchFinish($b);

        $b = Profiler::benchStart(\Profiler::BENCH_CAT_CORE, 'Process URI');
        if ($this->route == null) {
            $this->route = Route::process_uri('');
        }
        Profiler::benchFinish($b);

        if ($this->route == null) {
            // if still
            throw new Exception('No routers defined for empty path');
        }

        Profiler::addLog('Route processed and is ' . (($this->route == NULL) ? 'not found' : 'found'));

        if (Core::app()->hasLoadedModule('toolbar')) {
            Core::app()->toolbar->putValueToTab('Request', 'Route processed and is ' . (($this->route == NULL) ? 'not found' : 'found'));
        }

        // running controller
        $b = Profiler::benchStart(\Profiler::BENCH_CAT_CORE, 'Running controller + events');

        Core::event(self::EVENT_BEFORE_DISPATCH);

        $this->_runController($this->route);

        Core::event(self::EVENT_AFTER_DISPATCH);

        Profiler::benchFinish($b);
        Profiler::addLog('Route handled');

        // sending response
        $this->response->send();

        Core::saveAutoloadCache();

        Profiler::addLog('Finishing application');
        if ($this->mode != Core::MODE_PROD and $this->cfg('debug/profiler') == 'enabled') {
            echo Profiler::getLogs();
            echo Profiler::getNotices();
        }
        return true;
    }

    private function _runController($route)
    {
        if (($ca = $this->_createController($route)) !== NULL) {
            list($controller, $action) = $ca;
            /* @var $route Route */
            /* @var $controller Controller */

//            if ($route !== null) {

            Profiler::addLog(
                'Dispatching: <code>' . $controller->area . '> ' . get_class($controller) . '/' . $action. '</code>'
            );

            /* @var $controller Controller */

//                if (empty($this->_route_regex)) {
//                    Profiler::addLog('No route for <code>' . $this->controller . '</code>');
//                    return $controller->unknownAction();
//                } else {
            try {
                return $controller->run($route);
            } catch (DbError $e) {
                ob_get_clean();
                throw new Exception('DB Error: ' . $e->getMessage());
            } catch (Exception $e) {
                ob_get_clean();
                throw new Exception(
                    'Dispatch action: <er>' . get_class($controller) . '->' . $action . '</er> :<br/>'. $e->getMessage());
            }
//                }
//            }
        } else {
            throw new Exception('not a controller');
        }
    }

    /**
     * @param $route
     *
     * @return array (controller, action)
     */
    private function _createController($route)
    {
        $paths = array('_default' => '/');

        if ($route != NULL) {
            foreach (array_keys($this->_loadedModules) as $path) {
                $paths[$path] = '/modules/' . $path . '/';
            }
            $classToCreate = $className = ucfirst($route->controller) . 'Controller';
            $areaName = '';
            if (!empty($route->area)) {
                $areaName = $route->area . '/';
                $classToCreate = ucfirst($route->area) . '_' . $className;
            }

            $areas = array($areaName);
            if (Core::app()->lang->usingLanguages) {
                array_unshift($areas, $areaName . '_' . Core::app()->lang->currentLanguage  . '/');
            }

//        var_dump($areaName);

            foreach (array('app' => TIKORI_ROOT, 'core' => TIKORI_FPATH) as $module => $source) {
                foreach ($paths as $path) {
                    //TODO: better list of folders created by module initializer
                    foreach($areas as $area) {
                        $file = $source . ($module == 'core' ? '' : '/' . $module) . $path . 'controllers/' . $area . $className . '.php';
                        Profiler::addNotice($file . ' <kbd>' . __FILE__ . ':' . __LINE__.'</kbd>');
                        Profiler::addNotice((int) file_exists($file));
                        if (file_exists($file)) {
                            try {
                                // TODO: autload should be used here I think...
                                include_once $file;
                                if (class_exists($classToCreate, false)) {
                                    /* @var $classToCreate Controller */
                                    $class = new $classToCreate($route->area, $route->scope);
                                } else {
                                    throw new Exception('Class not found');
                                }
                                $class->module = $module;
    //                        $route->dispatch($class);
                                return (array($class, $route->action));
                            } catch (Exception $e) {
    //                        var_dump($e);
                                Profiler::addLog($e->getMessage(), Profiler::LEVEL_IMPORTANT);
                                #$class = new Controller($route->area);
                                #$class->httpStatusAction(500);
                                return array(new Controller(), 'httpStatusAction');
                            }
                        }
                    }
                }
            }
        }

        return array(new Controller(), NULL);
    }

    /**
     * Returns base url for app
     *
     * @return string URL, like http://foo.bar/
     */
    public function baseUrl()
    {
        //return (isset(Core::app()->request->env)) ? Core::app()->request->env['tikori.base_url'] : '/';

        return (!empty($this->request)) ? $this->request->getBaseUrl(true) : '/';
    }
}

/**
 * @return string|null
 * @throws Exception
 */
function __()
{
    $args = func_get_args();
    if (Core::app()->lang != NULL) {
        return Core::app()->lang->translate($args);
    }
}

function clamp($min, $current, $max)
{
    return max($min, min($max, $current));
}
