<?php
namespace apps\models;

use apps\classes\PubFunction;
use apps\models\Star;

class HotTopicShare extends BaseModel
{
    const CACHE_EXPIRED = 300;

    public static function getList()
    {/*{{{*/
        $list = self::getMcClient()->get(self::getCacheKey());
        if(!$list)
        {
            $list = self::getListFromDb();
            $list && self::getMcClient()->set(self::getCacheKey(), $list, self::CACHE_EXPIRED);
        }
        return $list;

    }/*}}}*/

    public static function clearCache()
    {
        $list = self::getListFromDb();
        return self::getMcClient()->set(self::getCacheKey(), $list, self::CACHE_EXPIRED);
    }

    public static function getListFromDb()
    {/*{{{*/
        $list = [];
        $sql = 'select content,type from ' . self::tableName();
        $result = self::getSlaveDb()->queryAll($sql);
        $list = [];
        foreach ($result as $item)
        {
            $list[self::getTypeAlias($item['type'])][] = $item['content'];
        }
        return $list;
    }/*}}}*/

    public static function getTypeAlias($type)
    {
        $alias = [
            0 => 'content',
            1 =>'morningContent',
            2 => 'eveningContent',
            3 => 'h5Content'
        ];
        return isset($alias[$type]) ? $alias[$type] : $alias[0];
    }

    public static function getCacheKey()
    {/*{{{*/
        return 'fantuan_hot_t_s_';
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'hot_topic_share_config';
    }/*}}}*/
}
