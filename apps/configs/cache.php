<?php
$cache = [];
switch(APP_ENV)
{
    case 'dev':
        $cache['master'] = array(
            'type' => 'Memcache',
            'use_memcached' => true, //使用memcached扩展
            'servers' => array(
                array(
                    'host' => '10.0.100.40',
                    'port' => 11211,
                    'weight' => 100,
                    'persistent' => true,
                ),
                array(
                    'host' => '10.0.100.40',
                    'port' => 11212,
                    'weight' => 100,
                    'persistent' => true,
                ),
            ),
        );
        $cache['cacheCluster2'] = array(
            'type' => 'Memcache',
            'use_memcached' => true, //使用memcached扩展
            'servers' => array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 100,
                    'persistent' => true,
                )
            ),
        );

        break;
    case 'stage':
        $cache['master'] = array(
            'type' => 'Memcache',
            'use_memcached' => true, //使用memcached扩展
            'servers' => array(
                array(
                    'host' => 'php-mc01',
                    'port' => 11211,
                    'weight' => 60,
                    'persistent' => true,
                ),
                array(
                    'host' => 'php-mc01',
                    'port' => 11211,
                    'weight' => 40,
                    'persistent' => true,
                ),

            ),
        );
        $cache['cacheCluster2'] = array(
            'type' => 'Memcache',
            'use_memcached' => true, //使用memcached扩展
            'servers' => array(
                array(
                    'host' => 'php-mc01',
                    'port' => 11211,
                    'weight' => 60,
                    'persistent' => true,
                ),
                array(
                    'host' => 'php-mc01',
                    'port' => 11211,
                    'weight' => 40,
                    'persistent' => true,
                ),

            ),
        );

        break;
     default:
         $cache['master'] = array(
             'type' => 'Memcache',
             'use_memcached' => true, //使用memcached扩展
             'servers' => array(
                 array(
                     'host' => 'star-php-mc-new01',
                     'port' => 11211,
                     'weight' => 60,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php-mc-new02',
                     'port' => 11211,
                     'weight' => 60,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php-mc-new03',
                     'port' => 11211,
                     'weight' => 50,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php-mc-new04',
                     'port' => 11211,
                     'weight' => 50,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php01',
                     'port' => 11211,
                     'weight' => 40,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php02',
                     'port' => 11211,
                     'weight' => 40,
                     'persistent' => true,
                 ),
                 array(
                     'host' => 'star-php03',
                     'port' => 11211,
                     'weight' => 40,
                     'persistent' => true,
                 ),
             ),
         );
        $cache['cacheCluster2'] = array(
            'type' => 'Memcache',
            'use_memcached' => true, //使用memcached扩展
            'servers' => array(
                array(
                    'host' => 'star-php-mc02',
                    'port' => 11211,
                    'weight' => 50,
                    'persistent' => true,
                ),
                array(
                    'host' => 'star-php-mc02',
                    'port' => 11213,
                    'weight' => 50,
                    'persistent' => true,
                ),
                array(
                    'host' => 'star-php-mc03',
                    'port' => 11211,
                    'weight' => 50,
                    'persistent' => true,
                ),
                array(
                    'host' => 'star-php-mc03',
                    'port' => 11213,
                    'weight' => 50,
                    'persistent' => true,
                ),
            ),
        );

         break;
}
return $cache;
