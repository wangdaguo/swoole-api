<?php
namespace apps\models;
use apps\classes\LibShmCache;

class BannerManage extends BaseModel
{

    const ONLINE_ON = 1;
    const ONLINE_OFF = 0;

    const PLATFORM_ALL= 0;
    const PLATFORM_ANDROID = 1;
    const PLATFORM_IOS = 2;

    public static function tableName()
    {/*{{{*/
        return 'banner_manage';
    }/*}}}*/

    public static function clearCache($sid, $type, $platform)
    {/*{{{*/
        self::getBannerList($sid, $type, $platform, false);
        return true;
    }/*}}}*/
    public static function getBannerList($sid, $type, $platform, $useCache = true)
    {/*{{{*/
        $shmCacheKey = $cacheKey = 'banner_manage_' . $sid . '_' . $type. '_' . $platform;
        $mcClient = self::getMcClient();
        if($useCache)
        {
            if($bannerList = LibShmCache::get($shmCacheKey))
            {
                return $bannerList;
            }
            if($bannerList = $mcClient->get($cacheKey))
            {
                LibShmCache::set($shmCacheKey, $bannerList, 10);
                return $bannerList;
            }
        }
        $bannerList = self::getBannerListFromDb($sid, $type, $platform);
        if($bannerList)
        {
            LibShmCache::set($shmCacheKey, $bannerList, 10);
            $mcClient->set($cacheKey, $bannerList, 300);
            return $bannerList;
        }

        return [];
    }/*}}}*/

    public static function getBannerListFromDb($sid, $type, $platform)
    {/*{{{*/
        $sql = "SELECT id, sid, title, `desc`, images, type, createTime, sort, redirect FROM " . self::tableName();
        $sqlWhere = " WHERE sid=:sid and type=:type and onlined=:onlined and platform in (0, :platform)";
        $sqlOrder = " ORDER BY `sort` DESC";
        $binds = [
            ':sid' => $sid,
            ':type' => $type,
            ':onlined' => self::ONLINE_ON,
            ':platform' => $platform,
        ];
        $exeSql = $sql . $sqlWhere . $sqlOrder;
        return self::getSlaveClusterDb()->queryAll($exeSql, $binds);
    }/*}}}*/

}
