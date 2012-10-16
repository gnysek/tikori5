<!DOCTYPE html>
<html>
	<head>
		<title><?php echo Core::cfg('appName'); ?> 404 error</title>
		<link rel="stylesheet" type="text/css" href="/media/framework.css"/>
	</head>
	<body>
		<h1><?php echo Core::cfg('appName'); ?></h1>
		<h2>404: Not found</h2>
		<p><?php echo $this->content; ?>.</p>
		<?php if (Core::getMode() < Core::MODE_PROD) : ?>
			This page isn't under PROD mode, debug data:<br/>
			Request: <pre class="debug-pre"><?php echo var_export(Core::$request->env, true); ?></pre>
		<?php endif;
		?>
		<p class="footer">&copy; 2012 gnysek.pl</p>
	</body>
</html>
