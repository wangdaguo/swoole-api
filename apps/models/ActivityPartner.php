<?php
namespace apps\models;
use Swoole;
use apps\classes\LibShmCache;

class ActivityPartner extends BaseModel
{
    function getListByActivityId($activityId, $useCache = true)
    {/*{{{*/
        
        $localShmKey = 'activity_partnerlist_' . $activityId;
        $cacheKey = 'activity_partnerlist_' . $activityId;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $list = LibShmCache::get($localShmKey);
            if($list)
            {
                return $list;
            }

            $list = $mcClient->get($cacheKey);
            if($list)
            {
                return $list;
            }
        }
        $list = self::getListFromDb($activityId);
        if($list)
        {
            $mcClient->set($cacheKey, $list, 300);
            LibShmCache::set($localShmKey, $list, 10);
        }
        return $list;
    }/*}}}*/

    public static function getListFromDb($activityId)
    {/*{{{*/
        $sql = 'select id,name,url,logo from ' . self::tableName() . ' where activity_id=' . intval($activityId);
        return self::getSlaveClusterDb()->queryAll($sql);
    }/*}}}*/


    public static function tableName()
    {/*{{{*/
        return 'activity_list_partner';
    }/*}}}*/

}
