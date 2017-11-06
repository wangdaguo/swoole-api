<?php
namespace apps\models;

use apps\classes\LibShmCache;
use apps\models\Star;

class TaskRankWeek extends BaseModel
{
    const CACHE_EXPIRED = 600;

    public static function getList($week, $useCache = true)
    {/*{{{*/
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $list = $mcClient->get(self::getCacheKey($week));
            if($list)
            {
                return $list;
            }
        }
        $list = self::getFromDb($week);
        $list && $mcClient->set(self::getCacheKey($week), $list, self::CACHE_EXPIRED);
        return $list;

    }/*}}}*/

    public static function getFromDb($week)
    {/*{{{*/
        $sql = 'select rank, name,avatar, score from ' . self::tableName() 
            . ' where week = :week and rank > 0'
            .' order by rank asc limit 100';
        $binds = [
            ':week' => $week
        ];
        return self::getSlaveDb()->queryAll($sql, $binds);
        
    }/*}}}*/

    public static function getCacheKey($week)
    {/*{{{*/
        return 'task_rank_' . $week;
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'task_rank_week';
    }/*}}}*/

    public static function saveData($sid, $week, $score)
    {/*{{{*/
        try{
            $sql = 'insert into ' . self::tableName() . '
                (week, sid, score) 
                values 
                (:week, :sid, :score) ON DUPLICATE KEY UPDATE score = score + :score';
            $binds = [
                ':week' => $week,
                ':sid' => $sid,
                ':score' => $score
            ];
            return self::getMasterDb()->execute($sql, $binds);
        } catch(\Exception $e)
        {
            echo $e->getMessage();
            return false;
        }
    }/*}}}*/

    public static function updateRank($week)
    {
        $sql = 'select id,score,sid from ' . self::tableName() . '
            where week = :week order by score desc';
        $binds = [
            ':week' => $week
        ];
        $list = self::getMasterDb()->queryAll($sql, $binds);
        foreach($list as $key => $item)
        {
            $starInfo = Star::getInfoBySid($item['sid']);
            if($starInfo)
            {
                $rank = $key + 1;
                self::updateInfo($rank, $starInfo['name'], $starInfo['avatar'], $item['id']);
            }
        }
        self::getList($week, false);
    }

    public static function updateInfo($rank, $name, $avatar, $id)
    {
        $sql = 'update ' . self::tableName() . ' set rank = :rank, name=:name, avatar=:avatar where id = :id';
        $binds = [
            ':rank' => $rank,
            ':name' => $name,
            ':avatar' => $avatar,
            ':id' => $id
        ];
        return self::getMasterDb()->execute($sql, $binds);
    }
}
