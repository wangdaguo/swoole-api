<?php
namespace apps\models;
use apps\classes\LibShmCache;

class Copywriting extends BaseModel
{

    const FEATURE_START = 'start'; //开机屏
    const FEATURE_CATCH = 'catch'; //抓住他
    const FEATURE_MAP = 'map'; //
    const FEATURE_AD = 'ad'; //广告
    const FEATURE_APP = 'app'; //登录分享
    const FEATURE_ONLINE = 'online'; //
    const FEATURE_SOFA = 'sofa'; //抢沙发
    const LINESEPARATOR = "\r\n";

    const PLATFORM_ANDROID = 1;
    const PLATFORM_IOS = 2;

    public static function getData($platform, $feature, $useCache = true)
    {/*{{{*/
        $shmCacheKey = $cacheKey = 'copywriting_' . $platform . '_' . $feature;
        $redisClient = self::getClusterRedisClient();
        if($useCache)
        {
            if($result = LibShmCache::get($shmCacheKey))
            {
                return $result;
            }
            if($result = $redisClient->HGETALL($cacheKey))
            {
                LibShmCache::set($shmCacheKey, $result, 10);
                return $result;
            }
        }
        $result = self::getDataFromDb($platform, $feature);
        if($result)
        {
            LibShmCache::set($shmCacheKey, $result, 10);
            $redisClient->hmset($cacheKey, $result, 300);
            return $result;
        }
        return [];
    }/*}}}*/

    public static function getDataFromDb($platform, $feature)
    {/*{{{*/
        $sql = 'select title, content, images, redirectUrl from ' . self::tableName() . ' where feature = :feature and platform = :platform';
        $binds = [
            ':feature' => trim($feature),
            ':platform' => trim($platform)
        ];
        return self::getSlaveClusterDb()->queryOne($sql, $binds);
    }/*}}}*/

    public static function clearCache($platform, $feature)
    {/*{{{*/
        self::getData($platform, $feature, false);
        return true;
    }/*}}}*/


    public static function tableName()
    {/*{{{*/
        return 'copywriting';
    }/*}}}*/

    public static function isInteract($feature)
    {/*{{{*/
        return $feature == self::FEATURE_SOFA || self::FEATURE_CATCH;
    }/*}}}*/

    public static function getRandomContent($content)
    {/*{{{*/
        $contentArr = explode(self::LINESEPARATOR, trim($content));
        return $contentArr[array_rand($contentArr)];
    }/*}}}*/

}
