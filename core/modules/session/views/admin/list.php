<h1>List of users</h1>

<?php echo $this->widget(
    'Grid', array(
                 'data'     => $users,
                 'columns'  => array(
                     'id', 'name', 'sex', 'email', 'regdate', 'last_update_time', 'ban',
                 ),
                 'options'  => array(
                     'Edit'   => array('url' => array('users/edit', 'id' => ':id')),
                     'Delete' => array('url' => array('users/delete', 'id' => ':id')),
                     'View'   => array('url' => array('users/view', 'id' => ':id'))
                 ),
                 'titles'   => array('ban' => 'Active', 'last_update_time' => 'Last visit'),
                 'renderer' => array('sex' => 'sex', 'regdate' => 'date_long', 'last_update_time' => 'date_long', 'ban' => 'yesno'),
            ), true
); ?>
