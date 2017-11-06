<?php
namespace apps\models;

use apps\classes\PubFunction;
use apps\models\Star;

class Shine extends BaseModel
{
    const CACHE_EXPIRED = 3600;

    public static function getShines($uid, $currentSid, $count)
    {/*{{{*/
        $starList = Star::getStarShineList(0, $count);
        $result = [];
        $isAddCurrentStar = true;
        foreach ($starList as $sid => $score) {
            //$followerInfo = Follower::getInfoSid($sid);
            $starInfo = self::formatStarInfo($uid, $sid, $score);
            if (empty($starInfo)) {
                continue;
            }
            $result[] = $starInfo;
            if ($currentSid == $sid) {
                $isAddCurrentStar = false;
            }
        }
        if ($isAddCurrentStar) {
            $score = Star::getStarShineBySid($currentSid);
            $starInfo = self::formatStarInfo($uid, $currentSid, $score);
            if (!empty($starInfo)) {
                array_push($result, $starInfo);
            }
        }
        return $result;
    }/*}}}*/

    public static function formatStarInfo($uid, $sid, $score)
    {
        $data = [];
        $data['star'] = Star::getStarInfo($uid, $sid);
        if (empty($data['star'])) {
            return [];
        }
        $geoArr = Follower::formatGeo(Star::getStarGeo($sid));
        $data['geo'] = $geoArr ? $geoArr : new \stdClass;
        $data['totalShine'] = (int)$score;
        $data['shine'] = Star::getStarDetail($uid, $sid);
        return $data;
    }



    public static function formatGeo($geo)
    {/*{{{*/
        $result = [];
        if ($geo) {
            $geoArr = json_decode($geo, 1);
            foreach ($geoArr as $key => $val) {
                if (substr($key, 0, 3) != '001' && strlen($key) != 6) {
                    continue;
                }
                if ($val <= 500)
                {
                    $level = 1;
                } elseif($val <= 1000)
                {
                    $level = 2;
                } elseif($val <= 5000)
                {
                    $level = 3;
                } else {
                    $lelvel = 4;
                }
                $result[$key]= $level;
            }
        }
        return $result; 
    }/*}}}*/

    public static function getInfoSid($sid, $useCache = true)
    {/*{{{*/
        $cacheKey = self::getCacheKey($sid);
        if ($useCache)
        {
            $row = \Yii::$app->cache->get($cacheKey);
            if ($row)
            {
                return $row;
            }
        }
        $sql = 'select * from ' . self::tableName() . '
            where sid=:sid';
        $binds = [
            ':sid' => $sid
        ];
        $row = self::getDb()->createCommand($sql, $binds)->queryOne();
        $row && \Yii::$app->cache->set($cacheKey, $row, self::CACHE_EXPIRED);
        return $row;
    }/*}}}*/

    public function updateFollowerGeo($uid, $sids)
    {/*{{{*/
        $userInfo = User::getInfoByUid($uid);
    }/*}}}*/

    public static function getCacheKey($sid)
    {/*{{{*/
        return 'starfantuan_follower_' . $sid;
    }/*}}}*/


    public static function tableName()
    {/*{{{*/
        return 'follower';
    }/*}}}*/
}
