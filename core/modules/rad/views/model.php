<h1>Tikori5 Rapid Application Development</h1>
<h2>Create new Model</h2>

<ol>
    <?php foreach ($tables as $table): ?>
        <li><?php echo Html::link($table[0], array('rad/modelCreate', 'model' => $table[0])); ?></li>
    <?php endforeach; ?>
</ol>

