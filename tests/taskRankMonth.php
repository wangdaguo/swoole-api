<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/ShineController.php';
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\ShineController($php);
try{
    $list = $controller->actionMonth();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
