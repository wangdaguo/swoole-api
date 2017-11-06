<?php
namespace apps\models;

use yii;
use apps\models\BaseModel;

class TimelineStats extends BaseModel
{
    const CACHE_EXPIRED = 3600;

    public static function tableName()
    {/*{{{*/
        return 'timeline_stats';
    }/*}}}*/

    public static function getStatsList($sid, $date)
    {/*{{{*/
        $list = [];
        $item = [];
        if ($date == date('Y-m-d')) {
            $stats = self::getStatsFromOldRedis($sid, $date);
        } else {
            $stats = self::getStats($sid, $date);
        }
        if (empty($stats) || isset($stats['isNull'])) {
            return [];
        }
        foreach ($stats as $action => $num) {
            $item['action'] = $action;
            $item['num'] = $num;
            $list[] = $item;
        }
        return $list;
    }/*}}}*/

    public static function getStatsFromOldRedis($sid, $date)
    {/*{{{*/
        $redisClient = self::getSingleRedisClient();
        $statsKey = 'tl:' . date('Y-m-d') . ':' . $sid;
        return $redisClient->HGETALL($statsKey);
    }/*}}}*/

    public static function getStats($sid, $date)
    {/*{{{*/
        $binds = [
            ':sid' => $sid,
            ':date' => $date
        ];
        $sql = 'select infos from ' . self::tableName() . '
            where sid = :sid and date = :date';
        $row = self::getSlaveDb()->queryOne($sql, $binds);
        return $row ? json_decode($row['infos'], 1) : [];
    }/*}}}*/

}

