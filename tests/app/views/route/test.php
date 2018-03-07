<?php

echo '<h1>Route "test"</h1>';

$routeTest = array(
    'test' => array(
        'expr'     => 'test(/<tparams>)',
        'params'   => array(
            'tparams' => '[a-zA-Z0-9/]+',
        ),
        'defaults' => array(
            'controller' => 'someControllerClass',
            'action'     => 'default',
        ),
    ),
);

echo sprintf('<pre class="prettyprint lang-php">%s</pre>', htmlspecialchars(var_export($routeTest, true)));

Route::reconfigure($routeTest);

$routesAgainst = array('test', '/test', 'test/', '/test/', 'test/path/id', 'test/controller/dupa', 'test/path/id?test=value12&x=12', 'testowe');

foreach ($routesAgainst as $routeAgainst) {

    echo sprintf('<hr><kbd>Called uri: <strong>%s</strong></kbd><br>', $routeAgainst);

    $url = parse_url($routeAgainst);

    Core::app()->request->clearParams();
    if (!empty($url['query'])) {

        $params = explode('&', $url['query']);

        foreach($params as $param) {
            list($k, $v) = explode('=', $param, 2);
            Core::app()->request->forceParam($k, $v);
        }
    }

    $result = Route::process_uri($url['path']);

    if (empty($result)) {
        echo '<span style="color: red;">Not found</span>';
        continue;
    }

    echo '<strong>PERFORMS</strong>: <kbd>' . $result->controller . '->' . $result->action . 'Action()</kbd><br>';

    echo '<strong>Params:</strong><br>';
    foreach ($result->params as $k => $v) {
        echo '<kbd>' . $k . '</kbd>: <kbd>' . $v . '</kbd><br>';
    }

    echo 'scope: <kbd>' . $result->scope . '</kbd><br>';
    echo 'area: <kbd>' . $result->area . '</kbd><br>';
}