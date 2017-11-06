<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require APPPATH . '/models/Statistics.php';
use apps\models\Statistics;

global $php;
$routeArr = [
    '/h5/vote/vote',
    '/hot-topic-share/index',
    '/star/get-shine-list',
    '/shine/index',
    '/tasks/do',
    '/tasks/list',
    '/star/onlines',
    '/timeline/list',
    '/timeline/stats',
    '/timeline-custom/list',
    '/timeline-custom/list',
    '/h5/activity/info',
    '/h5/activity/play',
    '/h5/activity/join-list',
    '/h5/activity/award-list',
    '/h5/commonweal/list',
    '/h5/commonweal/info',
    '/h5/live-interview/recommend',
    '/h5/live-interview/list',
    '/h5/live-interview/description',
    '/h5/super-topic/index',
    '/h5/super-topic/topical-detail',
    '/h5/vote/info',
    '/h5/vote/vote',
    '/h5/vote/participate-list',
];
$time = time() - 60;
$timestamp = date('Y-m-d H:i', $time);
$timestamp .= ':00';
$hour = date('i', $time);
$statArr = [];
$hostname = gethostname();
foreach($routeArr as $route)
{
    $key = $hour . $route;
    $number = $php->shmCache->get($key);
    $number = $number ? $number : 0;
    Statistics::saveData($route, $timestamp, $number, $hostname);
}
