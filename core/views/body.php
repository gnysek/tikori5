<h1>Default action for <?php echo Core::app()->cfg('appName') ?></h1>

<p>Hello, world!</p>
<p>This is default action for Tikori5 framework.</p>

<?php foreach ($data as $value): ?>
	<?php echo $value; ?><br/>
	<?php var_dump($value); ?><br/>
<?php endforeach; ?>

