<?php
$this->breadcrumbs = (!empty($this->area)) ? array(ucfirst($this->area), $status) : array($status);
?>

<p>Sorry but this page is:</p>
<h1><?php echo $message ?></h1>
