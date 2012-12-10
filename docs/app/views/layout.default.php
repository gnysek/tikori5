<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?php echo Core::app()->cfg('appName'); ?></title>
		<link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/style.css"/>
	</head>
	<body>
		<div id="layout">
			<div id="inner-layout">
				<h1><?php echo Core::app()->cfg('appName'); ?></h1>
				<hr/>
				<div style="width: 225px; margin-right: 10px; float: left;"><?php echo $this->renderPartial('static/_menu'); ?></div>
				<div style="width: 535px; float: right;"><?php echo $content ?></div>
				<div style="clear: both;"></div>

			</div>
			<div class="footer">
				&copy; 2012 - <?php date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>
				<div class="pull-right">
					<?php if (DB::queries() > 0): ?>
						Database queries: <strong><?php echo DB::queries(); ?></strong> &bull;
					<?php endif; ?>
					Generated in: <strong><?php echo Core::genTimeNow(2); ?></strong>s.
				</div>
			</div>
		</div>
	</body>
</html>
