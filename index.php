<?php
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT | E_NOTICE);
include('core/Core.php');
Core::run(dirname(__FILE__));
