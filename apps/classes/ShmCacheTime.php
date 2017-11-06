<?php
namespace apps\classes;
class ShmCacheTime
{
    private static $config;
    private function __construct()
    {
    }
    public static function getConfig()
    {
        if(!isset(self::$config))
        {
            $configFile = dirname(__DIR__) . '/configs/shmcachetime.php';
            self::$config = include($configFile);
        }
    }

    public static function get($key, $defalut = 1)
    {
        self::getConfig();
        if(!empty(self::$config[$key]))
        {
            return self::$config[$key];
        } else {
            return $default;
        }
    }
}
