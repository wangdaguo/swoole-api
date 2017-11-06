<?php
namespace apps\models;

class Follower extends BaseModel
{
    const CACHE_EXPIRED = 3600;

    public static function getFollowers($uid, $sid, $count)
    {/*{{{*/
        $starList = Star::getList(0, $count - 1);
        $starInfo = Star::getInfoBySid($sid);
        array_unshift($starList, $starInfo);
        foreach ($starList as &$star) {
            $followerInfo = self::getInfoSid($star['id']);
            if ($followerInfo) {
                $star['geo'] = self::formatGeo($followerInfo['geo']);
            } else {
                $star['geo'] = new \StdClass();
            }
            $star['sid'] = $star['id'];
            $followInfo = Follow::getInfoByUidAndSid($uid, $star['id']);
            $star['follower_number'] = $followInfo['number'] ? $followInfo['number'] : -1;
        }
        return $starList;
    }/*}}}*/

    public static function formatGeo($geo)
    {/*{{{*/
        $result = [];
        if ($geo) {
            $geoArr = is_array($geo) ? $geo : json_decode($geo, 1);
            foreach ($geoArr as $key => $val) {
                if (substr($key, 0, 3) != '001' && strlen($key) != 6) {
                    continue;
                }
                if ($val <= 500) {
                    $level = 1;
                } elseif ($val <= 1000) {
                    $level = 2;
                } elseif ($val <= 5000) {
                    $level = 3;
                } else {
                    $level = 4;
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

    public static function addUpdateGeoData($uid, $sidsArr, $plus = 1)
    {/*{{{*/
        $cacheKey = self::getGeoListCacheKey();
        $data = [
            'uid' => $uid,
            'sids' => $sidsArr,
            'plus' => $plus
        ];
        self::getRedisClient()->lpush($cacheKey, json_encode($data));
    }/*}}}*/

    public static function getGeoListCacheKey()
    {/*{{{*/
        return 'starfantuan_geo_list_cache';
    }/*}}}*/

    public static function popUpdateGenData()
    {/*{{{*/
        $cacheKey = self::getGeoListCacheKey();
        return self::getRedisClient()->lpop();
    }/*}}}*/

    public static function executeGeo($data)
    {
        $data = json_decode($data, 1);
        $sidArr = $data['sids'];
        $uid = $data['uid'];
        $plus = $data['plus'];
        $userInfo = User::getInfoByUid($uid);
        if (!$userInfo || !$userInfo['device'])
        {
            return false;
        }
        if (!is_array($sidArr))
        {
            return false;
        }
        foreach ($sidArr as $sid)
        {
            $followerInfo = self::getInfoBySid($sid);
            if (empty($followerInfo))
            {
                continue;
            }
            $geoArr = json_decode($followerInfo['geo'], 1);
            if (array_key_exists($userInfo['province'], $genArr))
            {
                $geoArr[$userInfo['province']] += $plus;
            } else {
                $geoArr[$userInfo['province']] = $plus;
            }
            $geoArr[$userInfo['province']] = $geoArr[$userInfo['province']] > 0 ? $geoArr[$userInfo['province']] : 0;

            self::updateGeo($sid, $geoArr);
        }

    }

    public static function updateGeo($sid, $geoArr)
    {
        $sql = 'update ' . self::tableName() . '
            set geo = :geo where sid=:sid';
        $binds = [
            ':geo' => json_encode($geoArr),
            ':sid' => $sid
         ];
        self::getMasterDb()->execute($sql, $binds);
        self::getInfoBySid($sid, false);
    }
}
