<?php
$this->breadcrumbs = array(
    'Tests',
);

$links = array(
    array('title' => 'E_NOTICE', 'link' => 'errortest/notice'),
    array('title' => 'E_PARSE', 'link' => 'errortest/parse'),
    array('title' => 'E_FATAL', 'link' => 'errortest/fatal'),
    array('title' => 'E_USER', 'link' => 'errortest/user'),

);

array_map(function($element){
    echo '&bull; ';
    echo Html::link($element['title'], $element['link'], array('target' => '_blank'));
    echo '<br>';
}, $links);
