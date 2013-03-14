<?php

$msg = array(
    'Howdy',
    'Hello',
    'Hi',
    'Welcome',
    'It\'s you again'
);
$welcome = array_rand($msg);
?>

<h1><?php echo $msg[$welcome]; ?>, <?php echo Core::app()->session->user()->name; ?>!</h1>

Here you can review your profile.
