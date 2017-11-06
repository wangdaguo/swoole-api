<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/models/Star.php';
require WEBPATH . '/apps/models/TaskRankWeek.php';
require WEBPATH . '/apps/classes/PubFunction.php';
use apps\models\Star;
use apps\models\TaskRankWeek;
use apps\models\TaskRankMonth;
use apps\classes\PubFunction;
$date = date('Y-m-d', time() - 86400);
$consoleFile = '/data/logs/fantuan/task.log';
error_log("exec start:$date\n", 3, $consoleFile);
$weekArr = PubFunction::getWeek($date);
$week = $weekArr[0] . '-' . $weekArr[1];

$monthArr = PubFunction::getMonth($date);
$month = $monthArr[0] . '-' . $monthArr[1];

$dateKey = date('Ymd', time() - 86400);
$list = Star::getStarShineList(0, 0, $dateKey);
foreach($list as $sid => $score)
{
    $result = TaskRankWeek::saveData($sid, $week, $score);
    $log = date('Y-m-d H:i:s') . "week\t$sid\t$score\t$result\n";
    $file = '/data/logs/fantuan/taskrank_' . date('Ymd') . '.log';
    error_log($log, 3, $file);


    $result = TaskRankMonth::saveData($sid, $month, $score);
    $log = date('Y-m-d H:i:s') . "month\t$sid\t$score\t$result\n";
    $file = '/data/logs/fantuan/taskmonth_' . date('Ymd') . '.log';
    error_log($log, 3, $file);
}
TaskRankWeek::updateRank($week);
TaskRankMonth::updateRank($month);
$date = date('Ymd', time() - 86400);
Star::deleteShineList($dateKey);
error_log("exec end:$date\n", 3, $consoleFile);
