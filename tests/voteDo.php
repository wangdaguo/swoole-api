<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/controllers/H5VoteController.php';
$_POST['vid'] = 54;
$_REQUEST['userToken'] = '61euYFw8e1174210wGi';
$_POST['maxOptionNum'] = 1;
$_POST['options'][] = 124;
global $php;
$controller = new \apps\controllers\H5VoteController($php);
try{
    $list = $controller->actionVote();
} catch (\Exception $e)
{
    $message =  $e->getMessage();
    print_r(json_decode($message,1));
}
echo "\n";

