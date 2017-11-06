<?php
namespace apps\models;

use apps\classes\LibShmCache;

class TopicalInformation extends BaseModel
{
    const TOPICAL_INFORMATION_LIBSHMCACHE = 'lib_topical_information_';
    const TOPICAL_INFORMATION_LIBSHMCACHE_EXPIRATION = 10;
    const TOPICAL_INFORMATION_MEMCACHE = 'topical_information_';
    const TOPICAL_INFORMATION_MEMCACHE_EXPIRATION = 600;

    public static function tableName()
    {
        return 'topical_information';
    }

    private static function getLibShmCacheKey($id)
    {
        return self::TOPICAL_INFORMATION_LIBSHMCACHE.$id;
    }

    private static function getCacheKey($id)
    {
        return self::TOPICAL_INFORMATION_MEMCACHE.$id;
    }

    public static function getTopicalInformationFromMemcache($id, $useCache = true)
    {
        $localShmCacheKey = self::getLibShmCacheKey($id);
        $cacheKey = self::getCacheKey($id);
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {

            $topicalInformation = LibShmCache::get($localShmCacheKey);
            if ($topicalInformation)
            {
                return json_decode($topicalInformation, true);
            }

            $topicalInformation = $mcClient->get($cacheKey);
            if ($topicalInformation)
            {
                LibShmCache::set($localShmCacheKey, $topicalInformation, self::TOPICAL_INFORMATION_LIBSHMCACHE_EXPIRATION);
                return json_decode($topicalInformation, true);
            }

        }
        $topicalInformation = self::getTopicalInformation($id);
        if (!$topicalInformation)
        {
            return '';
        }
        LibShmCache::set($localShmCacheKey, json_encode($topicalInformation), self::TOPICAL_INFORMATION_LIBSHMCACHE_EXPIRATION);
        $mcClient->set($cacheKey, json_encode($topicalInformation), self::TOPICAL_INFORMATION_MEMCACHE_EXPIRATION);
        return $topicalInformation;

    }

    public static function getTopicalInformation($id)
    {
        $sql = 'select * from '.self::tableName().' where id = :id';
        $binds = [
            ':id' => intval($id)
        ];
        $superTopic = self::getSlaveClusterDb()->queryOne($sql, $binds);
        $superTopic['postTime'] = date('Y-m-d', strtotime($superTopic['postTime']));
        return $superTopic;
    }

}
