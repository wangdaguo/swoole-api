<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/models/Star.php';
use apps\models\Star;

foreach ([120381,4264,3579,3,44] as $sid)
{
    Star::updateStarShine($sid, rand(1,5000));
}

$list = Star::getStarShineList(0, 0);
echo '<pre>';
print_r($list);exit;

