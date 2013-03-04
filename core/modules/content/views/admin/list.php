<?php $this->breadcrumbs = array(
	'Admin' => array('/'),
	'Content' => array('content'),
	'All'
); ?>

<?php if (!empty($collection) and count($collection) > 0): ?>
<ol>
	<?php foreach ($collection as $node): ?>
	<?php /* @var $node ContentTranslation */ ?>
	<li>[ <?php echo Html::link('e',array('content/edit')); ?> | <?php echo Html::link('d',array('content/delete')); ?> ] <?php echo Html::link($node->name, array('content', 'node' => $node->id)) ?></li>
	<?php endforeach; ?>
</ol>
<?php else: ?>
Sorry, no articles found.
<?php endif; ?>
