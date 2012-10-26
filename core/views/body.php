<h1>Default action for <?php echo Core::app()->cfg('appName') ?></h1>

<p>Hello, world!</p>
<p>This is default action for Tikori5 framework.</p>

<strong>Test 1:</strong>
<br/>
<?php foreach ($test1 as $value): ?>
	<?php echo $value; ?><br/>
<?php endforeach; ?>
<hr/>
<strong>Test 2:</strong>
<br/>
<?php echo $test2; ?>

