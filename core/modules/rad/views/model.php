<h1>Tikori5 Rapid Application Development</h1>
<h2>Create new Model</h2>

<ol>
    <?php foreach ($tables as $table): ?>
        <li><?php
			$exists = true;
			try {
				$modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $table[0])));
				$class = new $modelName;
			} catch (Exception $e) {
				#echo $e->getMessage();
				$exists = false;
			}; ?>
			<?php echo Html::link($table[0], array('rad/modelCreate', 'model' => $table[0]), array('style'=>($exists) ? 'background-color: yellowgreen;' : '')); ?> <kbd>class <?php echo $modelName; ?></kbd>
		</li>
    <?php endforeach; ?>
</ol>

