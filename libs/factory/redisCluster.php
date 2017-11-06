<?php
global $php;

$config = $php->config['redis'][$php->factory_key];

$redisCluster = new Swoole\Component\RedisCluster($config);
return $redisCluster;