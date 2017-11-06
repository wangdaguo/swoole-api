<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
global $php;
$config = $php->config['cache']['master'];
$taskId = 1;
$task = json_encode((new apps\models\Tasks($php))->getTaskData(1));
foreach($config['servers'] as $key => $server)
{
    $originConfig = $config;
    $originConfig['servers'] = [];
    $originConfig['servers'][] = $server;
    test($originConfig, $task);
}

function test($config, $task)
{
    $number = 10;
    $cache = new \Swoole\Cache\Memcache($config);
    $fileName = $config['servers'][0]['host'] . '.log';
    $key = 'sft:1:13:1';
    $cache->set($key, $task, 100);
    for($i = 0; $i < $number; $i++)
    {
        $start = microtime(true);
        $result = $cache->get($key);
        $end = microtime(true);
        $cost = ($end - $start) . "\n";
        error_log($cost, 3, "/tmp/$fileName");
    }
}
