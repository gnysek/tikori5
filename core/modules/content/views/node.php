<?php $this->breadcrumbs = array(
    #'Admin' => array(''),
    'Content' => array('content'),
    '#' . $node->content->id . ' - ' . $node->name
); ?>

<h2><?php echo $node->content->id ?>. <?php echo $node->name ?></h2>
<?php echo $node->short ?>
