<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
echo "redisCluster:method";
echo "\n";
print_r(get_class_methods('RedisCluster'));
echo "\n";
global $php;
$redisCluster = $php->redisCluster('cluster');
try{
    $key = 'redis_cluster';
    for($i = 0; $i < 1000;$i++)
    {
        $redisCluster->set($key . $i, $i);
    }
    for($i = 0; $i < 1000;$i++)
    {
        var_dump($redisCluster->get($key . $i));
    }

} catch(\Exception $e)
{
    var_dump($e->getMessage());
}
