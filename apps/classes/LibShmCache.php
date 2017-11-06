<?php
namespace apps\classes;
use Swoole;
class LibShmCache
{
    private static $instance;
    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = Swoole::getInstance()->shmCache;
        }
        return self::$instance;
    }

    public static function get($key)
    {
        return self::getInstance()->get($key);
    }
    public static function set($key, $value, $duration = ShmCache::NEVER_EXPIRED)
    {
        return self::getInstance()->set($key, $value, $duration);
    }

    public static function delete($key)
    {
        return self::getInstance()->delete($key);
    }

    public static function stats()
    {
        return self::getInstance()->stats();
    }

    public static function flush()
    {
        return self::getInstance()->clear();
    }
}
