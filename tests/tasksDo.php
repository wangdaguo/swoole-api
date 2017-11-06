<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/TasksController.php';
$_POST['sid'] =1;
$_POST['taskId'] = 49;
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\TasksController($php);
try{
    $list = $controller->actionDo();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
