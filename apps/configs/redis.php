<?php

$redis = [];
switch(APP_ENV)
{
    case 'dev':
        $redis['master'] = array(
            'host'    => "127.0.0.1",
            'port'    => 6379,
            'password' => '',
            'timeout' => 0.25,
            'pconnect' => false,
        );
        $redis['cluster'] = array(
            'timeout' => 1,
            'readTimeout' => 1,
            'pconnect' => true,
            'servers' => [
                [
                    'host'    => "dev-redis-cl01",
                    'port'    => 6381 
                ],
                [
                    'host'    => "dev-redis-cl01",
                    'port'    => 6382 
                ],
                [
                    'host'    => "dev-redis-cl02",
                    'port'    => 6381 
                ],
                [
                    'host'    => "dev-redis-cl02",
                    'port'    => 6382 
                ],
                [
                    'host'    => "dev-redis-cl03",
                    'port'    => 6381 
                ],
                [
                    'host'    => "dev-redis-cl03",
                    'port'    => 6382 
                ],

            ]
        );
        break;

    case 'stage':
        $redis['master'] = array(
            'host'    => "upstage-redis01.mqfrtr.0001.cnn1.cache.amazonaws.com.cn",
            'port'    => 6379,
            'password' => '',
            'timeout' => 1,
            'pconnect' => true,
        );
        $redis['cluster'] = array(
            'timeout' => 1,
            'readTimeout' => 1,
            'pconnect' => true,
            'servers' => [
                [
                    'host'    => "10.0.3.60",
                    'port'    => 6381
                ],
                [
                    'host'    => "10.0.3.60",
                    'port'    => 6382
                ],
                [
                    'host'    => "10.0.3.60",
                    'port'    => 6383 
                ],
                [
                    'host'    => "10.0.3.70",
                    'port'    => 6384 
                ],
                [
                    'host'    => "10.0.3.70",
                    'port'    => 6385 
                ],
                [
                    'host'    => "10.0.3.70",
                    'port'    => 6386 
                ],

            ]
        );
        break;

    default:
        $redis['master'] = array(
            'host'    => "star-php-redis-s1",
            'port'    => 6379,
            'password' => '',
            'timeout' => 1,
            'pconnect' => true,
        );
        $redis['cluster'] = array(
            'timeout' => 1,
            'readTimeout' => 1,
            'pconnect' => true,
            'servers' => [
                [
                    'host'    => "dd-redis-cls01",
                    'port'    => 6381
                ],
                [
                    'host'    => "dd-redis-cls02",
                    'port'    => 6381
                ],
                [
                    'host'    => "dd-redis-cls03",
                    'port'    => 6381
                ]
            ]
        );
        break;
}

return $redis;
