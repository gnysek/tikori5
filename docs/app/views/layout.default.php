<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?php echo Core::app()->cfg('appName'); ?></title>
		<link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>/../media/demo.css"/>
	</head>
	<body>
		<div id="layout">
			<div id="inner-layout">
				<h1><?php echo Core::app()->cfg('appName'); ?></h1>
				<?php echo $content; ?>
			</div>
			<div class="footer">
				&copy; 2012 - <?php date('Y'); ?> <a href="http://gnysek.pl" target="_blank">gnysek.pl</a>
				<div class="pull-right">
					<?php if (DB::queries() > 0): ?>
						Database queries: <strong><?php echo DB::queries(); ?></strong> &bull;
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
