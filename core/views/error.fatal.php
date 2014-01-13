<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?= Core::app()->cfg('appName'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/fatal.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/prettify.css"/>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/prettify.js"></script>
</head>
<body onload="prettyPrint();">
<h1><?= Core::app()->cfg('appName'); ?> Critical Error</h1>

<?php if (Core::app()->getMode() !== Core::MODE_PROD): ?>
    <p><strong>A problem has been detected and Tikori5 framework was unable to complete request:</strong></p>

    <p><?= $message; ?>.</p>
    <hr/>
    <p><strong>Technical information:</strong></p>
    <hr/>
    <code style="display: block;"><span class="str"><?= $file ?>:<?= $line ?></span></code>
    <p>Requested <span><?= $reqMethod ?></span>: <?= $reqPath ?></p>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $key => $text): ?>
            <a href="#ex-<?php echo $key; ?>"><?php echo $text; ?></a><br/>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    foreach ($files as $entry) {
        echo $entry;
    }
    ?>

    <?php if (Core::app()->mode != Core::MODE_PROD) {
        echo Profiler::getLogs();
    } ?>

    <?php else: ?>
    <p><strong>Because of critical error in our code, server is unable to complete your request.</strong></p>
    <p><a href="/">Go back to homepage</a></p>
<?php endif; ?>
<hr/>
<div class="footer">
    &copy; 2003 - <?= date('Y') ?> <?= Core::app()->cfg('appName'); ?> &bull; <?= date('d/m/Y H:i:s'); ?>
</div>
</body>
</html>
