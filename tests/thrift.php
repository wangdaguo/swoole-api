<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
$obj = new \apps\classes\thrift\FollowTClient();
$r = $obj->updateFollowShine(1,1,10);
var_dump($r);
