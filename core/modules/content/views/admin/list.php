<?php $this->breadcrumbs = array(
    'Admin'   => array('/'),
    'Content' => array('content'),
    'All'
); ?>

<?php echo $this->widget(
    'Grid', array(
                 'columns' => array('id', 'enabled', 'name', 'path', 'childrenCount', 'parent'),
                 'data'    => $content,
                 'options' => array(
                     'Edit'     => array('url' => array('content/edit', 'id' => ':id')),
                     'Delete'   => array('url' => array('content/delete', 'id' => ':id')),
                     'View'     => array('url' => array('content/view', 'id' => ':id')),
                     'Subnodes' => array('url' => array('content/nodes', 'id' => ':id')),
                     'Author'   => array('url' => array('content/author', 'id' => ':id')),
//                         'Show'   => array('url' => array('content', 'visibility' => ':id')),
                 )
            ), true
); ?>

<br/>

<?php if (!empty($collection) and count($collection) > 0): ?>
    <?php echo $this->widget(
        'Grid', array(
                     'columns' => array('id', 'name', 'language', 'url'),
                     'data'    => $collection,
                     'options' => array(
                         'Edit'   => array('url' => array('content/edit', 'id' => ':id')),
                         'Delete' => array('url' => array('content/delete', 'id' => ':id')),
                         'View'   => array('url' => array('content/view', 'id' => ':id')),
                         'Author' => array('url' => array('content/author', 'id' => ':id')),
//                         'Show'   => array('url' => array('content', 'visibility' => ':id')),
                     )
                ), true
    ); ?>
<?php else: ?>
    Sorry, no articles found.
<?php endif; ?>
