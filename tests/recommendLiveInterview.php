<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/H5LiveInterviewController.php';
$_POST['page'] = 1;
$_POST['pageSize'] = 10;
global $php;
$controller = new \apps\controllers\H5LiveInterViewController($php);
try{
    $list = $controller->actionDescription();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
