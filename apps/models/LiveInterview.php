<?php
namespace apps\models;
use apps\classes\PubFunction;

class LiveInterview extends BaseModel
{

    public static function getRecommendList($userCache = false)
    {/*{{{*/
        $data = [];
        $data['summary'] = LiveInterviewSummary::getInfo();
        $data['commonweal'] = Commonweal::getHotList();
        $mcClient = self::getCacheCluster2Client();
        $listCacheKey = 'liveinterview_recommend_list_cache';
        $list = $mcClient->get($listCacheKey);
        if(!$list)
        {
            $list  = self::getRecommendListFromDb();
            $list && $mcClient->set($listCacheKey, $list, 300);
        }
        $data['list'] = $list;
        return $data;
    }/*}}}*/
    public static function getList($page, $pageSize)
    {/*{{{*/
        $data = [];
        $listCacheKey = 'liveinterview_list_cache';
        $list = [];
        $mcClient = self::getCacheCluster2Client();
        if($page == 1)
        {
            $list = $mcClient->get($listCacheKey);
            if(!$list)
            {
                $list  = self::getListFromDb($page, $pageSize);
                $list && $mcClient->set($listCacheKey, $list, 300);
            }
        }
        if(!$list)
        {
            $list  = self::getListFromDb($page, $pageSize);
        }
        $data['list'] = $list;
        return $data;
    }/*}}}*/


    public static function clearCache()
    {/*{{{*/
        $mcClient = self::getCacheCluster2Client();
        $listCacheKey = 'liveinterview_list_cache';
        $mcClient->delete($listCacheKey);
        self::getList(1, 10);
        $listCacheKey = 'liveinterview_recommend_list_cache';
        $mcClient->delete($listCacheKey);
        self::getRecommendList();
        return true;
    }/*}}}*/

    public static function getListFromDb($page, $pageSize)
    {/*{{{*/
        $limit = ($page - 1) * $pageSize;
        $list = [];
        $sql = 'select id,sid, avatar,nickname, duration, timeStr, startTime, endTime,videoTitle,typeStatus,url,bill,viewers,cover from ' . self::tableName() . '
            where type = 2 and isCommonWeal = 1 and typeStatus = 4  and status = 1
            order by createTime desc 
            limit '. $limit . ','. $pageSize;
        return self::getStarBaseDb()->queryAll($sql);
    }/*}}}*/
    public static function getRecommendListFromDb()
    {/*{{{*/
        $limit = ($page - 1) * $pageSize;
        $list = [];
        $sql = 'select id,sid, avatar,nickname, timeStr,duration, startTime, endTime,videoTitle,typeStatus,url,bill,viewers,cover from ' . self::tableName() . '
            where type = 2 and isCommonWeal = 1 and typeStatus = 4  and status = 1
            and commonweal_recommend = 1 order by createTime desc';
        return self::getStarBaseDb()->queryAll($sql);
    }/*}}}*/



    public static function tableName()
    {/*{{{*/
        return 'live_interview';
    }/*}}}*/
}
