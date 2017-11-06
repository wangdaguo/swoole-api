<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/CopywritingController.php';
$_POST['feature'] = 'sofa';
$_SERVER['HTTP_USERAGENT'] = 'ios/localDevelop version/2.0.0';
global $php;
$controller = new \apps\controllers\CopywritingController($php);
try{
    $list = $controller->actionIndex();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";
