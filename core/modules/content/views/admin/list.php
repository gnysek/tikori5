<?php $this->breadcrumbs = array(
    'Admin'   => array('/'),
    'Content' => array('content'),
    'All'
); ?>


<?php if (!empty($collection) and count($collection) > 0): ?>
    <?php echo $this->widget(
        'Grid', array(
                     'columns' => array('id', 'name', 'language', 'url'),
                     'data'    => $collection,
                     'options' => array(
                         'Edit'   => array('url' => array('content', 'edit' => ':id')),
                         'Delete' => array('url' => array('content', 'delete' => ':id')),
                         'View'   => array('url' => array('content', 'view' => ':id')),
                         'Author' => array('url' => array('content', 'author' => ':id')),
//                         'Show'   => array('url' => array('content', 'visibility' => ':id')),
                     )
                ), true
    ); ?>
<?php else: ?>
    Sorry, no articles found.
<?php endif; ?>
