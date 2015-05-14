<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo Core::app()->cfg('appName'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>../docs/media/style.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>../docs/media/prettify.css"/>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>../docs/../media/prettify.js"></script>
</head>
<body onload="prettyPrint();">
<div id="layout">
    <div id="inner-layout" class="cream-light-bg">

        <h1><?php echo Core::app()->cfg('appName'); ?> <?php echo Core::VERSION ?></h1>
        <hr/>
        <div
            style="width: 224px; margin-right: 10px; float: left; display: inline; border-right: 1px solid #333;"><?php echo $this->renderPartial(
                'static/_menu'
            ); ?></div>
        <div style="width: auto; display: block; float: left;">
            <?php
            if (!empty($this->breadcrumbs)) {
                $breadcrumbs = $this->widget(
                    'Breadcrumbs', array(
                        'links' => $this->breadcrumbs,
                    ), true
                );

                echo $breadcrumbs;
            }
            ?>
            <?php echo $content ?>
            <?php
            if (!empty($this->breadcrumbs)) {
                echo $breadcrumbs;
            }
            ?>
        </div>
        <div style="clear: both;"></div>

    </div>
    <div class="footer red-more-bg">
        &copy; 2003 - <?php echo date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>

        <div class="pull-right">
            <?php echo Core::poweredBy(); ?> &bull; Generated in: <strong><?php echo Core::genTimeNow(2); ?></strong>s.
        </div>
    </div>
</div>
</body>
</html>
