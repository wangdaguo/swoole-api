<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/H5SuperTopicController.php';
$_POST['id'] = 1;
global $php;
$controller = new \apps\controllers\H5SuperTopicController($php);
try{
    $list = $controller->actionIndex();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
