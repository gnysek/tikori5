<h2>Default action</h2>

<?php echo Html::url('default'); ?><br/>
<?php echo Html::url(array('default/test')); ?><br/>
<?php echo Html::url(array('default/test', 'id' => '1')); ?><br/>

<p>Hello, world!</p>
<p>This is default action for Tikori5 framework.</p>

<strong>Test 1:</strong>
<br/>
<?php foreach ($test1 as $value): ?>
    <?php echo $value; ?><br/>
<?php endforeach; ?>
<hr/>
<h4>Test 2:</h4>
<br/>
<?php echo $test2; ?>

