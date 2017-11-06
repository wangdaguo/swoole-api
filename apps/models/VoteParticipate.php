<?php
namespace apps\models;

class VoteParticipate extends BaseModel
{
    const PAGE_ONE = 1;
    const PAGE_SIZE = 50;

    const PARTICIPATE_MAX_NUM = 50;


    public static function tableName($vid)
    {/*{{{*/
        return 'vote_participate_new_' . $vid % 100;
    }/*}}}*/

    public static function getJoinOptionIds($vid, $uid)
    {/*{{{*/
        $cacheKey = self::getJoinCacheKey($vid, $uid);
        $mcClient = self::getCacheCluster2Client();
        $cacheValue = $mcClient->get($cacheKey);
        if(empty($cacheValue))
        {
            $cacheValue = self::getOptionIdsFromDb($vid, $uid);
            if($cacheValue)
            {
                $mcClient->set($cacheKey, $cacheValue, 300);
            }
        }
        return $cacheValue;
    }/*}}}*/

    public static function getJoinCacheKey($vid, $uid)
    {/*{{{*/
        return "vote_join_option_{$vid}_{$uid}";
    }/*}}}*/

    public static function getOptionIdsFromDb($vid, $uid)
    {/*{{{*/
        $sql = 'select optionId from ' . self::tableName($vid) . '
            where questionId = :vid and uid = :uid';
        $binds = [
            ':vid' => $vid,
            ':uid' => $uid
        ];
        $result = self::getSlaveClusterDb()->queryOne($sql, $binds);
        if($result)
        {
            return explode(',', $result['optionId']);
        }
        return [];
    }/*}}}*/


    public static function getList($vid, $page, $pageSize, $lastId = 0)
    {/*{{{*/
        if (0 == $lastId) {
            $list = self::getListFromMc($vid, $page, $pageSize, $lastId);
            $list = self::formatData($list);
            return $list;
        }
        $list = self::getListFromDb($vid, $page, $pageSize, $lastId);
        $list = self::formatData($list);
        return $list;
    }/*}}}*/

    public static function formatData($list)
    {
        foreach($list as &$item)
        {
            $item['createTime'] = date('Y-m-d H:i', strtotime($item['createTime']));
        }
        return $list;
    }

    
    public static function getListFromMc($vid, $page, $pageSize, $lastId, $useCache = true)
    {/*{{{*/
        $cacheKey = 'vote_participate_' . $vid;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            if($result = $mcClient->get($cacheKey))
            {
                return $result;
            }
        }
        $result = self::getListFromDb($vid, $page, $pageSize, $lastId);
        $mcClient->set($cacheKey, $result, 300);
        return $result;
    }/*}}}*/
    public static function getListFromDb($vid, $page, $pageSize,$lastId)
    {/*{{{*/
        $offset = ($page -1) * $pageSize;
        $sql = " select id,name,avatar,createTime from " . self::tableName($vid) . " where questionId = :questionId ";
        if($lastId > 0)
        {
            $sql .= ' and id < :id ';
            $binds[':id'] = $lastId;
        }         
        $sql .= " order by createTime desc limit  $pageSize";
        $binds[":questionId"] = $vid;
        return self::getSlaveClusterDb()->queryAll($sql, $binds);
    }/*}}}*/

    public static function saveData($vid, $uid, $options, $userInfo)
    {/*{{{*/
        $dataArr = [];
        $connection = self::getMasterDb();
        $sql = 'insert into ' . self::tableName($vid) . ' 
            ( questionId, name, avatar, uid, optionId)values
            ( :questionId, :name, :avatar, :uid, :optionId)';
        $binds = [
            ':questionId' => $vid,
            ':uid' => $uid,
            ':optionId' => implode(',', $options),
            ':name' => $userInfo['username'],
            ':avatar' => $userInfo['avatar']
        ];
        return $connection->execute($sql, $binds);
    }/*}}}*/

    public static function checkIpLimit($vid, $ip)
    {/*{{{*/
        $cacheKey = md5("vote_ip_limit_{$vid}_{$ip}"); 
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
