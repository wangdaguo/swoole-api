<?php
namespace apps\models;
use Swoole;

class BaseModel extends Swoole\Model
{
    public static function getMcClient()
    {
        return Swoole::getInstance()->cache('master');
    }

    public static function getCacheCluster2Client()
    {
        return Swoole::getInstance()->cache('cacheCluster2');
    }

    public static function getSingleRedisClient()
    {
        return Swoole::getInstance()->redis('master');
    }

    public static function getClusterRedisClient()
    {
        return Swoole::getInstance()->redisCluster('cluster');
    }

    public static function getSlaveDb()
    {
        return Swoole::getInstance()->db('fantuanSlaveClusterDb');
    }
    public static function getMasterDb()
    {
        return Swoole::getInstance()->db('fantuan_master');
    }
    public static function getStarBaseDb()
    {
        return Swoole::getInstance()->db('starbase');
    }


    public static function setTrack($track)
    {
        Swoole::getInstance()->profile->setTrack('m->' . $track);
    }

    public static function getSlaveClusterDb()
    {
        return Swoole::getInstance()->db('fantuanSlaveClusterDb');
    }



}
