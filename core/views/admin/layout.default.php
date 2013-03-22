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
        <ul class="unstyled">
            <li><a href="<?php echo Html::url(''); ?>" class="admin-home" title="Admin home"></a></li>
            <li class="active"><a href="<?php echo Html::url('content'); ?>" class="admin-content" title="Content"></a></li>
            <li><a href="<?php echo Html::url('users'); ?>" class="admin-users" title="Users"></a></li>
        </ul>
    </div>
    <ul class="nav-tabs">
        <li><a><span>Test</span></a></li>
        <li><a><span>Test 2</span></a></li>
    </ul>
    <div id="inner-layout" class="cream-light-bg">

        <ul class="nav-tabs">
            <li><a><span>Test</span></a></li>
            <li><a><span>Test</span></a></li>
        </ul>
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
