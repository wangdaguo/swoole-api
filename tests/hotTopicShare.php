<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/HotTopicShareController.php';
$_POST['type'] = 1;
global $php;
$controller = new \apps\controllers\HotTopicShareController($php);
try{
    $list = $controller->actionIndex();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";
