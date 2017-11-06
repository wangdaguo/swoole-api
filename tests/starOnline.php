<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/StarController.php';
$_POST['sids'] = json_encode([4]);;
$_SERVER['HTTP_USERTOKEN'] = '61eOHnidcf218e10wEI';
global $php;
$controller = new \apps\controllers\StarController($php);
try{
    $list = $controller->actionOnline();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
