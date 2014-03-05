<?php
$this->breadcrumbs = array(
    'Installation',
);
?>

    <h4>Basic configuration</h4>

    <p>Following variables may be added to configuration files in JSON format:<br/>
        <small>Those marked with * are required.</small>
    </p>

<?php
$configs = array(
    'appName'   => array('req' => true, 'desc' => 'name of your application. Will be displayed in &lt;title&gt; tag using default layouts.'),
    'host'      => 'force to use this page as homepage when creating urls (when no mod_rewrite available)',
    'default'   => array('req' => true, 'desc' => 'default controller name'),
    'db'        => array(
        'desc' => 'database config',
        'sub'  => array(
            'dbtype'   => 'driver type - pdo or mysqli',
            'dbhost'   => '',
            'dbuser'   => '',
            'dbpass'   => '',
            'dbname'   => '',
            'dbprefix' => 'prefix for tables',
            'dblink'   => '<kbd>mysql:host=127.0.0.1;dbname=test</kbd> - link for pdo',
        ),
    ),
    'url'       => array(
        'sub' => array(
            'addScriptName'  => '<kbd>true</kbd> or <kbd>false</kbd> - add index.php in url or no (useful when no mod_rewrite available)',
            'pathInsteadGet' => '<kbd>true</kbd> or <kbd>false</kbd> - use ?r= in url, instead of nice SEO path'
        )
    ),
    'areas'     => 'array with areas in application, for which request url should be cut',
    'routes'    => array(
        'desc' =>
        'create routing rules, see ' . Html::link('Controllers and routes', 'static/extend/controllers-and-routes'),
        'sub'  => array(
            '[routeName]' => array('req' => true, 'desc' => 'your custom route name', 'sub' => array(
                'expr'     => array('req' => true,),
                'params'   => array('req' => true, 'sub' => array(
                    '[paramName]' => array('req' => true, 'desc' => 'regular expression to validate that param from <kbd>expr</kbd>')
                )),
                'defaults' => array('req' => true, 'sub' => array(
                    'controller'        => array('req' => true,),
                    'action'            => array('req' => true,),
                    '[yourCustomParam]' => array(),
                )),
            )),
        )
    ),
    'languages' => array('sub' => array(
        'list'       => 'array of available languages',
        'type'       => '<kbd>subdomains|areas|cookie|session</kbd>',
        'subdomains' => array('sub' => array(
            '[langSubdomainName]' => 'value will be subdomain name (prefix) from which language will be set'
        )),
    )),
    'modules'   => 'list of modules',
);

function display($config, $level = 0)
{
    echo '<ul>';
    foreach ($config as $configName => $configData) {
        if ($level == 0) {
            echo '<li style="list-style-type: none; margin-left: -25px;"><strong>' . ucfirst($configName) . '</strong>  <a href="#"><kbd>&uarr;</kbd></a></li>';
        }
        echo '<li>';
        if ($level == 0) {
            echo '<a name="' . $configName . '"></a>';
        }
        echo '<code>' . $configName . '</code> ';
        if (!is_array($configData)) {
            echo '- ' . $configData;
        } else {
            if (!empty($configData['req'])) {
                echo '<span style="color: red;">*</span> ';
            }

            if (!empty($configData['desc'])) {
                echo '- ' . $configData['desc'];
            }

            if (!empty($configData['sub'])) {
                display($configData['sub'], $level + 1);
            }
        }
        echo '</li>';
    }
    echo '</ul>';
}

function links($config)
{
    echo '<ul>';
    foreach ($config as $configName => $configData) {
        echo '<li>';
        echo '<a href="#' . $configName . '">' . ucfirst($configName) . ' <tt>&rarr;</tt></a>';
        echo '</li>';
    }
    echo '</ul>';
}

?>
    <h5>Groups</h5>
<?php links($configs); ?>
    <h5>Details</h5>
<?php display($configs);
