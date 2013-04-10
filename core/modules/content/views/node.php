<?php $this->breadcrumbs = array(
    'Admin' => array(''),
    'Content' => array('content'),
    '#' . $node->content->id . ' - ' . $node->name
); ?>

<h2><?= $node->content->id ?>. <?= $node->name ?></h2>
<?= $node->short ?>
