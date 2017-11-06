<?php
namespace apps\models;

use apps\classes\LibShmCache;

class TaskRankMonth extends BaseModel
{
    const CACHE_EXPIRED = 600;

    public static function getList($month, $useCache = true)
    {/*{{{*/
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $list = $mcClient->get(self::getCacheKey($month));
            if($list)
            {
                return $list;
            }
        }
        $list = self::getFromDb($month);
        $list && $mcClient->set(self::getCacheKey($month), $list, self::CACHE_EXPIRED);
        return $list;

    }/*}}}*/

    public static function getFromDb($month)
    {/*{{{*/
        $sql = 'select rank, name,avatar, score from ' . self::tableName() 
            . ' where month = :month and rank > 0'
            .' order by rank asc limit 100';
        $binds = [
            ':month' => $month
        ];
        return self::getSlaveDb()->queryAll($sql, $binds);
        
    }/*}}}*/

    public static function getCacheKey($month)
    {/*{{{*/
        return 'task_rank_' . $month;
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'task_rank_month';
    }/*}}}*/

    public static function saveData($sid, $month, $score)
    {
        try{
            $sql = 'insert into ' . self::tableName() . '
                (month, sid, score) 
                values 
                (:month, :sid, :score) ON DUPLICATE KEY UPDATE score = score + :score';
            $binds = [
                ':month' => $month,
                ':sid' => $sid,
                ':score' => $score
            ];
            return self::getMasterDb()->execute($sql, $binds);
        } catch(\Exception $e)
        {
            echo $e->getMessage();
            return false;
        }
    }
    public static function updateRank($month)
    {
        $sql = 'select id,score,sid from ' . self::tableName() . '
            where month = :month order by score desc';
        $binds = [
            ':month' => $month
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
        self::getList($month, false);
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
