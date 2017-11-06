<?php
namespace apps\models;

use apps\classes\LibShmCache;
use apps\models\SuperTopicRelation;

class SuperTopic extends BaseModel
{
    const SUPER_TOPIC_LIBSHMCACHE = 'lib_super_topic_';
    const SUPER_TOPIC_LIBSHMCACHE_EXPIRATION = 10;
    const SUPER_TOPIC_MEMCACHE = 'super_topic_';
    const SUPER_TOPIC_MEMCACHE_EXPIRATION = 600;

    public static function tableName()
    {
        return 'super_topic';
    }

    private static function getLibShmCacheKey($id)
    {
        return self::SUPER_TOPIC_LIBSHMCACHE.$id;
    }

    private static function getCacheKey($id)
    {
        return self::SUPER_TOPIC_MEMCACHE.$id;
    }

    public static function getSuperTopicInfoFromMemcache($id, $useCache = true)
    {
        $localShmCacheKey = self::getLibShmCacheKey($id);
        $cacheKey = self::getCacheKey($id);
        $mcClient = self::getCacheCluster2Client();

        if($useCache)
        {
            $superTopicInfo = LibShmCache::get($localShmCacheKey);
            if ($superTopicInfo)
            {
                return json_decode($superTopicInfo, true);
            }

            $superTopicInfo = $mcClient->get($cacheKey);
            if ($superTopicInfo)
            {
                LibShmCache::set($localShmCacheKey, $superTopicInfo, self::SUPER_TOPIC_LIBSHMCACHE_EXPIRATION);
                return json_decode($superTopicInfo, true);
            }
        }

        $topicSuper = self::getTopicSuperAllData($id);
        if (!$topicSuper)
        {
            return '';
        }
        LibShmCache::set($localShmCacheKey, json_encode($topicSuper), self::SUPER_TOPIC_LIBSHMCACHE_EXPIRATION);
        $mcClient->set($cacheKey, json_encode($topicSuper), self::SUPER_TOPIC_MEMCACHE_EXPIRATION);
        return $topicSuper;

    }

    public static function getTopicSuperAllData($id)
    {
        $newSuperTopicInfo = [];
        $superTopic = self::getTopicSuper($id);
        if (!$superTopic)
        {
            return '';
        }

        $topicInfo['title'] = $superTopic['title'];
        $topicInfo['content'] = $superTopic['content'];
        $topicInfo['backgroupColor'] = $superTopic['backgroupColor'];
        $topicInfo['url'] = $superTopic['url'];
        $newSuperTopicInfo['topic'] = $topicInfo;

        $newSuperTopicInfo = SuperTopicRelation::getSuperTopicRelation($newSuperTopicInfo, $id);

        return $newSuperTopicInfo;

    }

    public static function getTopicSuper($id)
    {
        $sql = 'select * from '.self::tableName().' where id = :id and status = 1';
        $binds = [
            ':id' => intval($id),
        ];
        $superTopic = self::getSlaveClusterDb()->queryOne($sql, $binds);
        return $superTopic;
    }

}
