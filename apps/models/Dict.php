<?php
namespace apps\models;

use apps\models\Star;
use apps\classes\LibShmCache;

class Dict extends BaseModel
{
    const CACHE_EXPIRED = 300;
    const AD_CARD = 'ad_card';

    public static function getInfoByKey($key, $useLocalCache = true)
    {/*{{{*/
        $shmCacheKey = 'shm_dict_' . $key;
        if($useLocalCache)
        {
            $value =LibShmCache::get($shmCacheKey);
            if($value)
            {
                return $value;
            }
        }
        $mcClient = self::getCacheCluster2Client();
        $value = $mcClient->get(self::getCacheKey($key));
        if(!$value)
        {
            $value = self::getFromDb($key);
            $value && $mcClient->set(self::getCacheKey($key), $value, self::CACHE_EXPIRED);
        }
        if($value)
        {

            LibShmCache::set($shmCacheKey, $value, 10);
        }
        return $value;

    }/*}}}*/

    public static function clearCache($key)
    {
        $mcClient = self::getCacheCluster2Client();
        $mcClient->delete(self::getCacheKey($key));
        self::getInfoByKey($key, false);
        return true;
    }

    public static function getFromDb($key)
    {/*{{{*/
        $sql = 'select value from ' . self::tableName() . ' where uniqueKey =:key';
        $binds = [
            ':key' => $key
        ];
        $result = self::getSlaveDb()->queryOne($sql, $binds);
        if($result)
        {
            return $result['value'];
        }
        return null;
        
    }/*}}}*/

    public static function getCacheKey($key)
    {/*{{{*/
        return 'fantuan_dict_' . $key;
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'dict';
    }/*}}}*/
}
