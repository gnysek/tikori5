<?php

class TikoriInjector extends Tikori {

    public $renderPartially = false;

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
        $this->observer->fireEvent('cookie_loaded');
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

        Profiler::addLog('Finished loading application');
        #if ($this->mode != Core::MODE_PROD and $this->cfg('debug/profiler') == 'enabled') {
            #echo Profiler::getLogs();
            #echo Profiler::getNotices();
        #}
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function processRequest() {
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
}
