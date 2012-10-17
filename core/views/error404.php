<!DOCTYPE html>
<html>
	<head>
		<title><?php echo Core::app()->cfg('appName'); ?> 404 error</title>
		<link rel="stylesheet" type="text/css" href="/media/error.css"/>
	</head>
	<body>
		<h1><?php echo Core::app()->cfg('appName'); ?></h1>
		<h2>404: Not found</h2>
		<p><?php echo $content; ?>.</p>
		<?php if (Core::app()->getMode() < Core::MODE_PROD) : ?>
			Page in debug/dev mode!<br/>
			Request params: <pre class="debug-pre"><?php echo var_export(Core::app()->request->env, true); ?></pre>
		<?php endif;
		?>
		<p class="footer">&copy; 2012 gnysek.pl</p>
	</body>
</html>