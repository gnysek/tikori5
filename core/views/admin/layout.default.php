<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo Core::app()->cfg('appName'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/demo.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/css/admin.css"/>
</head>
<body>
<div id="layout">
    <div class="header blue-less-bg cream">
        <h1>ADMIN <?php echo Core::app()->cfg('appName'); ?></h1>
    </div>
    <div id="side-bar">
        <?php
        echo $this->widget(
            'AdminMenu', array('links' => array(
                array('', 'admin-home', 'Admin Home'),
                array('content', 'admin-content', 'Content'),
                array('users', 'admin-users', 'Users'),
                array('warns', 'admin-users', 'Warns'),
                array('comments', 'admin-users', 'Users'),
                array('tags', 'admin-users', 'Users'),
                array('forum', 'admin-users', 'Users'),
                array('shoutbox', 'admin-users', 'Users'),
                array('media', 'admin-users', 'Users'),
                array('logs', 'admin-users', 'Users'),
                array('core', 'admin-users', 'Users'),
                array('widgets', 'admin-users', 'Users'),
            )), true
        );
        ?>
    </div>
    <ul class="nav-tabs">
        <li><a><span>Test</span></a></li>
        <li><a><span>Test 2</span></a></li>
    </ul>
    <div id="inner-layout" class="cream-light-bg">
        <div class="content">
            <?php
            if (!empty($this->breadcrumbs)) {
                $breadcrumbs = $this->widget(
                    'Breadcrumbs', array(
                                        'links' => $this->breadcrumbs,
                                   ), true
                );
            } else {
                $breadcrumbs = $this->widget(
                    'Breadcrumbs', array(
                                        'links' => array(Core::app()->cfg('appName')),
                                   ), true
                );
            }
            echo $breadcrumbs;
            ?>

            <?php echo $content; ?>
        </div>
    </div>
    <div class="footer brown-bg">
        &copy; 2012 - <?php date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>

        <div class="pull-right">
            <?php if (Core::app()->db->queries() > 0): ?>
                Database queries: <strong><?php echo Core::app()->db->queries(); ?></strong> &bull;
            <?php endif; ?>
            Generated in: <strong><?php echo Core::genTimeNow(); ?></strong>s.
        </div>
    </div>

    <?php if (Core::app()->mode != Core::MODE_PROD): ?>
        <?php $this->renderPartial('debug.default'); ?>
    <?php endif; ?>
</div>
</body>
</html>
