<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/TimelineCustomController.php';
$_POST['sid'] = 4;
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\TimelineCustomController($php);
try{
    $list = $controller->actionList();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";

