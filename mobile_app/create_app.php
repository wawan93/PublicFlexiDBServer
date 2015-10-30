<?php
require_once "./generation_utils.php";
$path = dirname(dirname(__FILE__)).'/application/';
echo $path;
create_app($path);