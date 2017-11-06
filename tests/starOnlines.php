<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/StarController.php';
$_POST['sid'] = 4;
$_POST['offset'] = 0;
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\StarController($php);
try{
    $list = $controller->actionOnlines();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
