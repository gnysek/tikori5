<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?php echo Core::app()->cfg('appName'); ?></title>
		<link rel="stylesheet" type="text/css" href="/media/demo.css"/>
	</head>
	<body>
		<?php echo $content ?>
		<div class="footer">
			&copy; 2012 - <?php date('Y'); ?> <a href="gnysek.pl" target="_blank">gnysek.pl</a>
		</div>
	</body>
</html>
