<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?= Core::app()->cfg('appName'); ?> Critical Error</title>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/fatal.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/prettify.css"/>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/prettify.js"></script>
</head>
<body onload="prettyPrint();">
<h1>Tikori5 Framework Critical Error</h1>

<p><strong>A problem has been detected and Tikori5 framework was unable to complete request:</strong></p>

<p><?= $message; ?>.</p>

<?php if (Core::app()->getMode() < Core::MODE_PROD): ?>
    <hr/>
    <p><strong>Technical information:</strong></p>
    <hr/>
    <code style="display: block;"><span class="str"><?= $file ?>:<?= $line ?></span></code>
    <p>Requested <span><?= $reqMethod ?></span>: <?= $reqPath ?></p>
    <?php
    foreach ($files as $entry) {
        echo $entry;
    }

    Profiler::getLogs();
    ?>
<?php endif; ?>
<hr/>
<div class="footer">
    &copy; 2003 - <?= date('Y') ?> gnysek.pl &bull; <?= date('d/m/Y H:i:s'); ?>
</div>
</body>
</html>
