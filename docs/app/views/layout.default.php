<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?php echo Core::app()->cfg('appName'); ?></title>
		<link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/style.css"/>
	</head>
	<body>
		<div id="layout">
			<div id="inner-layout" class="cream-light-bg">
				
				<h1><?php echo Core::app()->cfg('appName'); ?></h1>
				<hr/>
				<div style="width: 224px; margin-right: 10px; float: left; border-right: 1px solid #333;"><?php echo $this->renderPartial('static/_menu'); ?></div>
				<div style="width: 535px; float: right;"><?php echo $content ?></div>
				<div style="clear: both;"></div>

			</div>
			<div class="footer red-more-bg">
				&copy; 2012 - <?php date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>
				<div class="pull-right">
					Generated in: <strong><?php echo Core::genTimeNow(2); ?></strong>s.
				</div>
			</div>
		</div>
	</body>
</html>
