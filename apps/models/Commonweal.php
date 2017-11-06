<?php
namespace apps\models;
use apps\classes\PubFunction;

class Commonweal extends BaseModel
{

    public static function getList()
    {/*{{{*/
        $data = [];
        $listCacheKey = 'commonweal_list_cache';
        $list = [];
        $mcClient = self::getCacheCluster2Client();
        $list = $mcClient->get($listCacheKey);
        if(!$list)
        {
            $list  = self::getListFromDb();
            $list && $mcClient->set($listCacheKey, $list, 300);
        }
        $data['list'] = self::formatData($list);
        return $data;
    }/*}}}*/

    public static function getHotList()
    {/*{{{*/
        $data = [];
        $listCacheKey = 'commonweal_hot_list_cache';
        $list = [];
        $mcClient = self::getCacheCluster2Client();
        $list = $mcClient->get($listCacheKey);
        if(!$list)
        {
            $list  = self::getListFromDb(2);
            $list && $mcClient->set($listCacheKey, $list, 300);
        }
        return self::formatData($list);
    }/*}}}*/

    public static function formatData($list)
    {/*{{{*/
        foreach($list as &$item)
        {
            $item['postTime'] = date('Y-m-d', strtotime($item['postTime']));
        }
        return $list;
    }/*}}}*/



    public static function clearCache($id)
    {/*{{{*/
        $mcClient = self::getCacheCluster2Client();
        $listCacheKey = 'commonweal_list_cache';
        $mcClient->delete($listCacheKey);
        self::getList();
        $listCacheKey = 'commonweal_hot_list_cache';
        $mcClient->delete($listCacheKey);
        self::getHotList();

        $cacheKey = 'commonweal_info_'. $id;
        $mcClient->delete($cacheKey);
        self::getInfo($id);
        return true;
    }/*}}}*/

    public static function getListFromDb($hot = 1)
    {/*{{{*/
        $sql = 'select id,title,cover,poster,postTime,hot from ' . self::tableName() ;
        if($hot == 2)
        {
            $sql .= ' where hot = ' . intval($hot);
        }
        $sql .= ' order by postTime desc ';

        return self::getStarBaseDb()->queryAll($sql);
    }/*}}}*/
    public static function getInfo($id)
    {/*{{{*/

        $cacheKey = 'commonweal_info_'. $id;
        $mcClient = self::getCacheCluster2Client();
        $info = $mcClient->get($cacheKey);
        if($info)
        {
            $info['postTime'] = date('Y-m-d', strtotime($info['postTime']));
            return $info;
        }
        $sql = 'select id,title,cover,banner, poster, postTime, description from ' . self::tableName() . '
            where id = ' . $id;
        $info = self::getStarBaseDb()->queryOne($sql);
        $info && $mcClient->set($cacheKey, $info, 300);
        if($info)
        {
            $info['postTime'] = date('Y-m-d', strtotime($info['postTime']));
        }
        return $info;
    }/*}}}*/


    public static function tableName()
    {/*{{{*/
        return 'commonweal';
    }/*}}}*/
}
