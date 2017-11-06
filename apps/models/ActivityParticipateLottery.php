<?php
namespace apps\models;

class ActivityParticipateLottery extends BaseModel
{
    const PAGE_ONE = 1;
    const PAGE_SIZE = 50;

    const PARTICIPATE_MAX_NUM = 50;


    public static function tableName()
    {/*{{{*/
        return 'activity_list_participate_lottery';
    }/*}}}*/

    public static function getLotteryList($activityId, $page, $pageSize)
    {
        if($page == 1)
        {
            return self::getLotteryListFromCache($activityId, $page, $pageSize);
        } else {
            return  self::getLotteryListFromDb($activityId, $page, $pageSize);
        }

    }/*}}}*/

    public static function getLotteryListFromCache($activityId, $page, $pageSize, $useCache = true)
    {
        $cacheKey = 'activity_participate_lottery_' . $activityId;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $list = $mcClient->get($cacheKey);
            if($list)
            {
                return $list;
            }
        }
        $list = self::getLotteryListFromDb($activityId, $page, $pageSize);
        $mcClient->set($cacheKey, $list, 300);
        return $list;
    }
    
    public static function getLotteryListFromDb($activityId, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $sql = "select p.uid, p.name, p.avatar from " . self::tableName();
        $sql.= " as lt inner join ";
        $sql .= " activity_list_participate as p on p.id = lt.activity_participate_id";
        $sql .= " where lt.activity_id = :activityId ";
        $sql .= " order by lt.created desc limit $offset, $pageSize";
        $binds = [
            ":activityId" => $activityId,
        ];
        return self::getSlaveClusterDb()->queryAll($sql, $binds);
    }
}
