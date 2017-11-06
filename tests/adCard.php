<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/AdCardController.php';
$_POST['position'] = 'starHome';
$_POST['sid'] = 0;
global $php;
$controller = new \apps\controllers\AdCardController($php);
try{
    $list = $controller->actionIndex();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
