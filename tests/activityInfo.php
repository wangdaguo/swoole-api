<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/H5ActivityController.php';
$_POST['activityId'] = 50;
$_REQUEST['userToken'] = '61euYFw8e1174210wGi';
global $php;
$controller = new \apps\controllers\H5ActivityController($php);
try{
    $list = $controller->actionPlay();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";

