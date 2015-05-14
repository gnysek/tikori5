<?php
$this->breadcrumbs = array(
    'Request',
);

$links = array(
    array('title' => 'Headers', 'link' => 'requesttest'),
    array('title' => 'Headers + index.php', 'link' => 'index.php/requesttest'),
);

array_map(function($element){
    echo '&bull; ';
    echo Html::link($element['title'], $element['link'], array('target' => '_blank'));
    echo '<br>';
}, $links);
