<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/BannerManageController.php';
$_POST['sid'] = 2;
$_POST['type'] = 0;
$_SERVER['HTTP_USERAGENT'] = 'ios/localDevelop version/2.0.0';
global $php;
$controller = new \apps\controllers\BannerManageController($php);
try{
    $list = $controller->actionList();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";
