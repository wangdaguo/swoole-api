<?php
namespace apps\models;

class ActivityParticipate extends BaseModel
{
    const PAGE_ONE = 1;
    const PAGE_SIZE = 50;

    const PARTICIPATE_MAX_NUM = 50;


    public static function tableName()
    {/*{{{*/
        return 'activity_list_participate';
    }/*}}}*/

    public static function checkParticipated($activityId, $uid)
    {/*{{{*/
        $cacheKey = self::getJoinCacheKey($activityId, $uid);
        $mcClient = self::getCacheCluster2Client();
        $result = $mcClient->get($cacheKey);
        if(empty($result))
        {
            $result = self::getInfoFromDb($activityId, $uid);
            if($result)
            {
                $mcClient->set($cacheKey, 1, 300);
            }
        }
        return $result ? 1 : 0;
    }/*}}}*/

    public static function cacheParticipated($activityId, $uid)
    {
        $cacheKey = self::getJoinCacheKey($activityId, $uid);
        $mcClient = self::getCacheCluster2Client();
        $mcClient->set($cacheKey, 1, 300);
        return true;
    }

    public static function getJoinCacheKey($activityId, $uid)
    {/*{{{*/
        return "activity_join_{$activityId}_{$uid}";
    }/*}}}*/

    public static function getInfoFromDb($activityId, $uid)
    {/*{{{*/
        $sql = 'select uid from ' . self::tableName() . '
            where activity_id = :activityId and uid = :uid';
        $binds = [
            ':activityId' => $activityId,
            ':uid' => $uid
        ];
        $result = self::getSlaveClusterDb()->queryOne($sql, $binds);
        return $result; 
    }/*}}}*/


    public static function getList($activityId, $page, $pageSize)
    {/*{{{*/
        if (1 == $page) {
            $list = self::getListFromMc($activityId, $page, $pageSize);
            $list = self::formatData($list);
            return $list;
        }
        $list = self::getListFromDb($activityId, $page, $pageSize);
        $list = self::formatData($list);
        return $list;
    }/*}}}*/

    public static function formatData($list)
    {
        foreach($list as &$item)
        {
            $item['created'] = date('Y-m-d H:i', strtotime($item['created']));
        }
        return $list;
    }

    
    public static function getListFromMc($activityId, $page, $pageSize, $useCache = true)
    {/*{{{*/
        $cacheKey = 'vote_participate_' . $activityId;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $result = $mcClient->get($cacheKey);
            if($result)
            {
                return $result;
            }
        }
        $result = self::getListFromDb($activityId, $page, $pageSize);
        $mcClient->set($cacheKey, $result, 300);
        return $result;
    }/*}}}*/
    public static function getListFromDb($activityId, $page, $pageSize)
    {/*{{{*/
        $offset = ($page -1) * $pageSize;
        $sql = " select id,name,avatar,created from " . self::tableName() . " where activity_id = :activity_id ";
        $sql .= " order by created desc limit $offset, $pageSize";
        $binds[":activity_id"] = $activityId;
        return self::getSlaveClusterDb()->queryAll($sql, $binds);
    }/*}}}*/

    public static function participate($activityId, $uid, $userInfo)
    {/*{{{*/
        $connection = self::getMasterDb();
        if($connection->beginTransaction())
        {

            try {
                $sql = 'insert into ' . self::tableName($activityId) . ' 
                    ( activity_id, name, avatar, uid)values
                    ( :activity_id, :name, :avatar, :uid)';
                $binds = [
                    ':activity_id' => $activityId,
                    ':uid' => $uid,
                    ':name' => $userInfo['username'],
                    ':avatar' => $userInfo['avatar']
                ];
                $iResult = $connection->execute($sql, $binds);
                $updateActivityCommonSql = "update " . ActivityCommon::tableName() . " set number=number+1 where id = :activityId and endTime >= :endTime";
                $bindsActivityCommon = [
                    ":activityId" => $activityId,
                    ":endTime" => time(),
                ];
                $uResult = $connection->execute($updateActivityCommonSql, $bindsActivityCommon);
                if (!$uResult || !$iResult){
                    throw new \Exception("result failed");
                }
                $connection->commit();
                ActivityCommon::getInfo($activityId, false);
                self::getListFromMc($activityId, 1, 50,false);
            } catch (\Exception $e) {
                $connection->rollBack();
                return false;
            }

        }
    }/*}}}*/

    public static function checkIpLimit($activityId, $ip)
    {/*{{{*/
        $cacheKey = md5("activity_ip_limit_{$activityId}_{$ip}"); 
        $redisClient = self::getClusterRedisClient();
        $count = $redisClient->INCR($cacheKey);
        if ($count == 1) {
            $redisClient->EXPIRE($cacheKey, 60);
        }
        if ($count > self::PARTICIPATE_MAX_NUM) {
            return true;
        }
        return false;
    }/*}}}*/
}
