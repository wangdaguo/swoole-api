<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/H5SuperTopicController.php';
$_POST['id'] = 2;
global $php;
$controller = new \apps\controllers\H5SuperTopicController($php);
try{
    $list = $controller->actionTopicalDetail();
} catch (\Exception $e)
{
    echo $e->getMessage();
}
echo "\n";
