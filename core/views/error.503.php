<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo Core::app()->cfg('appName'); ?> 404 error</title>
    <style type="text/css">
        <?php include('error.css'); ?>
    </style>
</head>
<body>

<h1><?php echo __('Maintenance'); ?></h1>

<p><?php echo __('Sorry, but this website will be unavailable for several minutes. Please check back later.'); ?></p>

<p class="footer">&copy; <?php echo date('Y'); ?> gnysek.pl</p>
</body>
</html>
