<?php
namespace apps\models;
use apps\classes\PubFunction;

class AdCard extends BaseModel
{

    public static function getList($position, $sid)
    {/*{{{*/
        $mcClient = self::getCacheCluster2Client();
        $cacheKey = self::getCacheKey($position, $sid);
        $list = $mcClient->get($cacheKey);
        if(!$list)
        {
            $list  = self::getListFromDb($position, $sid);
            $list && $mcClient->set($cacheKey, $list, 300);
        }
        return $list;
    }/*}}}*/

    public static function getListFromDb($position, $sid)
    {
        $sql = 'select type,flag,startTime, endTime, image, content,protocol,sid from ' . self::tableName() . '
            where position = :position and sid = :sid 
            and endTime >= :now';
        $binds = [
            ':position' => $position,
            ':sid' => $sid,
            ':now' => date('Y-m-d H:i:s')
        ];
        return self::getSlaveDb()->queryAll($sql, $binds);
    }

    public static function getCacheKey($position, $sid)
    {
        return 'ad_card_cache_' . $position . '_' . $sid;
    }

    public static function clearCache($position, $sid)
    {
        $mcClient = self::getCacheCluster2Client();
        $cacheKey = self::getCacheKey($position, $sid);
        $mcClient->delete($cacheKey);
        self::getList($position, $sid);
        return true;
    }

    public static function tableName()
    {/*{{{*/
        return 'ad_card';
    }/*}}}*/
}
