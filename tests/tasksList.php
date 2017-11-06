<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/TasksController.php';
$_POST['sid'] =1;
$_POST['type'] = -1;
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\TasksController($php);
try{
    $list = $controller->actionList();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";
