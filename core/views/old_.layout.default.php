<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo (!empty($this->pageTitle)) ? $this->pageTitle : Core::app()->cfg('appName') ?></title>
    <?php echo Asset::cssAsset('media/demo.css'); ?>
</head>
<body>
<div id="layout">
    <div id="inner-layout" class="cream-bg">
        <div style="float: right; font-size: 12px; line-height: 14px;">
            <?php
            /* @var $this Controller */
            /* @var $user UserWidget */
            $user = $this->widget('User');
            ?>

            Welcome, <strong style="<?php echo $user->color ?>"><?php echo $user->username; ?></strong>!<br/>
            <?php if (!$user->logged): ?>
                <?php echo Html::link('Login', $user->loginUrl); ?> &bull;
                <?php echo Html::link('Register', $user->registerUrl); ?>
            <?php else: ?>
                <?php echo Html::link('Profile', $user->profileUrl); ?> &bull;
                <?php echo Html::link('Logout', $user->logoutUrl); ?>
            <?php endif; ?>
        </div>

        <h1><?php echo Core::app()->cfg('appName'); ?></h1>
        <hr/>
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
    <div class="footer red-less-bg">
        &copy; 2012 - <?php date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>

        <div class="pull-right">
            <?php if (Core::app()->db): ?>
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
