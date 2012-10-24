<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?= Core::app()->cfg('appName'); ?> Critical Error</title>
		<link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/fatal.css"/>
	</head>
	<body>
		<h1>Tikori5 Framework Critical Error</h1>
		<p><strong>A problem has been detected and Tikori5 framework was unable to complete request:</strong></p>
		<p><?= $message; ?>.</p>

		<?php if (Core::app()->getMode() < Core::MODE_PROD): ?>
			<p>Requested <span><?= $reqMethod ?></span>: <?= $reqPath ?></p>

			<div class="l">
				Technical information:<br/>
				<u>File:</u><br/>
				<code><?= $file ?>:#<?= $line ?></code>
				<br/><u>Stack:</u>
				<div class="code">
					<pre><?= $stack ?></pre>
				</div>
			</div>

			<?php
			foreach ($files as $entry) {
				echo $entry;
			}
			?>
		<?php endif; ?>

		<div class="stopka">
			&copy; 2003 - <?= date('Y') ?> gnysek.pl &bull; <?= date('d/m/Y H:i:s'); ?>
		</div>
	</body>
</html>
