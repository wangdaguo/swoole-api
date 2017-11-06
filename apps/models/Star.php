<?php
namespace apps\models;
use apps\models\BaseModel;
use apps\classes\CacheConfig;
use apps\classes\LibShmCache;
use apps\classes\PubFunction;
use Swoole;
class Star extends BaseModel
{

    const CACHE_EXPIRED = 3600;

    const ONLINE = 1001;
    const TWEET = 3000;
    const HOT_TOPIC = 7001;
    const JOURNEY = 8000;
    const INS = 9000;
    const INS_UPDATE = 9001;
    const INS_FOLLOW = 9002;
    const FANQUAN = 11000;

    const TYPE_DISABLED = -1;  # deleted (by weibo etc.)
    const TYPE_DEFAULT = 0;  # manual import
    const TYPE_PERSONAL = 1;  # add by user

    const TAG_NONE = 0;
    const TAG_HOT = 1;

    public static function getFollowed($uid)
    {/*{{{*/
        $followedList =  [
            [
                'verified' => 0,
                'name' =>  "Mr_凡先生",
                'wuid' => "3591355593",
                'rank' => 9,
                'followers_count' => 84790,
                'avatar' => "http://tva2.sinaimg.cn/crop.0.0.1242.1242.180/d60fbcc9jw8f0gpcqps8uj20yi0yi0ux.jpg",
                'online' => 0,  #是否在线
                'id' =>  10,
                'total_followers_count' => 7400640,
            ],
            [
                'verified' => 0,
                'name' =>  "先生",
                'wuid' => "3591355593",
                'rank' => 9,
                'followers_count' => 84790,
                'avatar' => "http://tva2.sinaimg.cn/crop.0.0.1242.1242.180/d60fbcc9jw8f0gpcqps8uj20yi0yi0ux.jpg",
                'online' => 0,  #是否在线
                'id' =>  11,
                'total_followers_count' => 7400640,
            ]

        ];
        return $followedList;
    }/*}}}*/

    public static function getStarByWuid1($wuid, $useCache = true)
    {/*{{{*/
        $cacheKey = self::getCacheKey($wuid);
        if ($useCache) {
            $row = \Yii::$app->cache->get($cacheKey);
            if ($row) {
                return self::filterData($row);
            }
        }
        $sql = 'select * from ' . self::tableName() . '
            where wuid = :wuid';
        $binds = [
            ':wuid' => $wuid
        ];
        $row = self::getDb()->createCommand($sql, $binds)->queryOne();
        $row = [];
        if ($row) {
            \Yii::$app->cache->set($cacheKey, $row, self::CACHE_EXPIRED);
        } else {
            $weiboStarInfo = self::getInfoFromWeibo($wuid);
            if ($weiboStarInfo) {
                self::createStar($weiboStarInfo);
            }
            $row = self::getDb()->createCommand($sql, $binds)->queryOne();
            $row && \Yii::$app->cache->set($cacheKey, $row, self::CACHE_EXPIRED);
        }
        return self::filterData($row);
    }/*}}}*/

    public static function createStar($weiboStarInfo, $type = self::TYPE_PERSONAL)
    {/*{{{*/
        $sql = 'insert into ' .self::tableName() . '
            (wuid, name, avatar, type, created, modified) values 
            (:wuid, :name, :avatar,:type, :created, :modified)';
        $binds = [
            ':wuid' => $weiboStarInfo['id'],
            ':name' => $weiboStarInfo['name'],
            ':avatar' => $weiboStarInfo['avatar_large'],
            ':type' => $type,
            ':created' => date('Y-m-d H:i:s'),
            ':modified' => date('Y-m-d H:i:s')
        ];
        return self::getDb()->createCommand($sql, $binds)->execute();
    }/*}}}*/

    public static function getCacheKey($wuid)
    {/*{{{*/
        return 'starfantuan_wuid_' . $wuid;
    }/*}}}*/

    public static function getInfoBySid($sid, $useCache = true)
    {/*{{{*/
        $cacheKey = self::getSidCacheKey($sid);
        $mcClient = self::getMcClient();
        if ($useCache) {
            $row = $mcClient->get($cacheKey);
            if ($row) {
                return self::filterData($row);
            }
        }
        $sql = 'select  * from ' . self::tableName() . '
            where id = :id';
        $binds = [
            ':id' => $sid
        ];
        $row = self::getSlaveDb()->queryOne($sql, $binds);
        $row && $mcClient->set($cacheKey, $row, self::CACHE_EXPIRED);
        return self::filterData($row);
    }/*}}}*/

    public static function getSidCacheKey($sid)
    {/*{{{*/
        return 'starfantuan_sid_' . $sid;
    }/*}}}*/

    public static function getStarInfoLibShmCacheKey($id)
    {/*{{{*/
        return CacheConfig::PREFIX_LIBSHMCACHE . '_' . CacheConfig::STAR_FANTUAN_LIB . '_' . CacheConfig::STAR_INFO_LIBSHMCACHE. '_' . $id;
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'star';
    }/*}}}*/

    public static function updateFollow($sid, $followNum, $tag = '')
    {/*{{{*/
        $sql = 'update ' . self::tableName() . '
            set followers_count= followers_count + ' . $followNum . '
            where id=:id';
        $binds = [
            ':id' => $sid
        ];
        if ($tag) {
            $sql .= ' and tag = :tag';
            $binds[':tag'] = $tag;
        }
        $result = self::getDb()->createCommand($sql, $binds)->execute();
        self::getInfoBySid($sid, false);
        return $result;
    }/*}}}*/

    public static function updateShine($sid, $shine)
    {/*{{{*/
        $sql = 'update ' . self::tableName() . '
            set shine = shine + ' . intval($shine). '
            where id=:id';
        $binds = [
            ':id' => $sid
        ];
        $result = self::getDb()->createCommand($sql, $binds)->execute();
        self::getInfoBySid($sid, false);
        return $result;
    }/*}}}*/

    public static function getList($offset, $limit = 10, $tag = '')
    {/*{{{*/
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = 'select 
            id, wuid, name, avatar, rank, verified, verified_type, 
            followers_count, total_followers_count
            from ' . self::tableName() . '
            where type != ' . self::TYPE_DISABLED;
        $binds = [];
        if ($tag) {
            $sql .= ' and tag=:tag';
            $binds = [
                ':tag' => $tag
            ];
        }
        $sql .= " order by followers_count DESC, id ASC limit $limit offset $offset";
        return self::getDb()->createCommand($sql, $binds)->queryAll();
    }/*}}}*/

    public static function getShineList($uid, $page, $count)
    {
        $result = [];
        $offset = ($page - 1) * $count;
        $starList = self::getStarShineList($offset, $count);
        self::setTrack('getShineList->getStarShineList->start');
        foreach ($starList as $sid => $score) {
            $starInfo = self::formatStarInfo($uid, $sid, $score);
            if (empty($starInfo)) {
                continue;
            }
            $result[] = $starInfo;
        }
        self::setTrack('getShineList->getStarShineList->end');
        return $result;
    }

    public static function getStarInfo($uid, $sid)
    {
        $starInfo = Star::getStarBySidFromMc($sid);
        if (empty($starInfo)) {
            return [];
        }
        $starInfo['sid'] = $sid;
        $starInfo['rank'] = Star::getStarShinePos($sid);
        //pb不支持下划线_
        $starInfo['followersCount']= $starInfo['followers_count'];
        unset($starInfo['followers_count']);
        $starInfo['totalFollowersCount']= $starInfo['total_followers_count'];
        unset($starInfo['total_followers_count']);
        return $starInfo;
    }

    public static function formatStarInfo($uid, $sid, $score)
    {
        $data = [];
        self::setTrack('formatStarInfo-in-start');
        $starInfo = self::getStarInfo($uid, $sid);
        self::setTrack('formatStarInfo-in-end');
        if (empty($starInfo)) {
            return [];
        }
        $data['star'] = $starInfo;
        $data['totalShine'] = (int)$score;
        $data['shine'] = Star::getStarDetail($uid, $sid);
        self::setTrack('formatStarInfo-in-Star::getStarDetail');
        return $data;
    }

    // redis shine cache
    public static function updateStarShine($sid, $shine)
    {/*{{{*/
        $cacheKey = self::getStarShineCacheKey();
        $redisClient = self::getSingleRedisClient();
        $redisClient->zincrby($cacheKey, intval($shine), $sid);
        $redisClient->expire($cacheKey, PubFunction::getRemainSeconds() + 86400);
    }/*}}}*/

    public static function getStarShineList($start = 0, $limit = 20, $date = '')
    {/*{{{*/
        $cacheKey = self::getStarShineCacheKey($date);
        $redisClient = self::getSingleRedisClient();
        $list = $redisClient->zrevrange($cacheKey, $start, $start + $limit -1, 'WITHSCORES');
        return $list;
    }/*}}}*/

    public static function getStarShineBySid($sid)
    {/*{{{*/
        $cacheKey = self::getStarShineCacheKey();
        $redisClient = self::getSingleRedisClient();
        return $redisClient->ZSCORE($cacheKey, $sid);
    }/*}}}*/

    public static function getStarShinePos($sid)
    {/*{{{*/
        $cacheKey = self::getStarShineCacheKey();
        $redisClient = self::getSingleRedisClient();
        $rank = $redisClient->zrevrank($cacheKey, $sid);
        if ($rank !== false) {
            return $rank + 1;
        } else {
            return -1;
        }
    }/*}}}*/

    public static function getStarShineCacheKey($date = '')
    {/*{{{*/
        $date = $date ? $date : date('Ymd');
        return 'star:shine:total:'. $date;
    }/*}}}*/

    public static function deleteShineList($date)
    {
        $cacheKey = self::getStarShineCacheKey($date);
        $redisClient = self::getSingleRedisClient();
        $redisClient->del($cacheKey);
        return true;
    }

    public static function updateStarGeo($sid, $province, $shine)
    {/*{{{*/
        $cacheKey = self::getStarGeoCacheKey();
        $redisClient = self::getSingleRedisClient();
        $data = self::getStarGeo($sid);
        if (!array_key_exists($province, $data)) {
            $data[$province] = $shine;
        } else {
            $data[$province] += $shine;
        }
        $redisClient->hset($cacheKey, $sid, json_encode($data));
        $redisClient->expire($cacheKey, PubFunction::getRemainSeconds());
    }/*}}}*/
    public static function getStarGeo($sid)
    {/*{{{*/
        $cacheKey = self::getStarGeoCacheKey();
        $redisClient = self::getSingleRedisClient();
        $data = $redisClient->hget($cacheKey, $sid);
        $data = @json_decode($data, true);
        $data = $data ? $data : [];
        return $data;

    }/*}}}*/
    public static function getStarGeoCacheKey()
    {/*{{{*/
        return 'star:shine:geo:' . date('Ymd');
    }/*}}}*/

    public static function updateStarDetail($uid, $sid, $shine)
    {/*{{{*/
        $cacheKey = self::getStarDetailCacheKey();
        $redisClient = self::getClusterRedisClient();
        $subCacheKey = "$uid:$sid";
        $redisClient->hincrby($cacheKey, $subCacheKey, $shine);
        $redisClient->expire($cacheKey, PubFunction::getRemainSeconds());
    }/*}}}*/

    public static function getStarDetail($uid, $sid)
    {/*{{{*/
        $cacheKey = self::getStarDetailCacheKey();
        $redisClient = self::getClusterRedisClient();
        $subCacheKey = "$uid:$sid";
        $shine = $redisClient->HGET($cacheKey, $subCacheKey);
        return $shine ? (int)$shine : 0;

    }/*}}}*/
    public static function getStarDetailCacheKey()
    {/*{{{*/
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::STAR_SHINE_DETAIL . ':' . date('Ymd');
    }/*}}}*/

    public static function getStarOnlineMcKey($sid)
    {/*{{{*/
        return CacheConfig::PREFIX_MC . ':' . CacheConfig::STARFANTUAN_MC . ':' . CacheConfig::STAR_ONLINE . ':' . $sid;
    }/*}}}*/

    public static function filterData($data)
    {/*{{{*/
        if ($data['type'] == self::TYPE_DISABLED) {
            return [];
        }
        return $data;
    }/*}}}*/

    public static function getInfoFromWeibo($wuid)
    {/*{{{*/
        $url = 'https://api.weibo.com/2/users/show.json';

        $weiboConfig = WeiboConfig::getOne();
        //$weiboConfig ['key'] = '529450260';
        //$weiboConfig ['secret'] = 'e014b2f45febcfd41bb5e266b29ffe09';
        //$weiboConfig ['token'] = '2.00JS7QpF01MWpZ2cd5ea3d28Uu6eBC';
        if ($weiboConfig) {
            $params = [
                'access_token' => $weiboConfig['token'],
                'uid' => $wuid
            ];
            $key = $weiboConfig['key'];
            $secret = $weiboConfig['secret'];
            $info = (new SaeTOAuthV2($key, $secret))->get($url, $params);
            return $info;
        }
        return [];

    }/*}}}*/

    public static function getOnlineStats($sidList)
    {/*{{{*/
        $list = [];
        if (1 == sizeof($sidList)) {
            $sid = $sidList[0];
            $online = self::getOnlineFromMc($sid);
            if (empty($online)) {
                return [
                ];
            }
            $onlineStatsList[] =  $online;
        } else {
            $onlineStatsList = self::getOnlineStatsListFromDb($sidList);
            if (empty($onlineStatsList)) {
                return [
                ];
            }
        }
        return self::formatOnlineStats($onlineStatsList);
    }/*}}}*/

    public static function getOnlineFromMc($sid)
    {
        $mcClient = self::getMcClient();
        $key = self::getStarOnlineMcKey($sid);
        $onlineJson = $mcClient->get($key);

        if (!$onlineJson) {
            $online = self::getOnlineBySidFromDb($sid);
            if (empty($online)) {
                return [];
            }
            $onlineJson = json_encode($online);
            $mcClient->set($key, $onlineJson, CacheConfig::CACHE_TIME_MC);
        }
        return json_decode($onlineJson, true);
    }

    public static function getOnlineBySidFromDb($sid)
    {/*{{{*/
        $sql = "SELECT id, online ";
        $sqlFrom = " FROM " . self::tableName();
        $sqlWhere = " WHERE id = :sid ";
        $execSql = $sql . $sqlFrom . $sqlWhere;
        $binds = [
            ":sid" => $sid,
        ];
        return self::getSlaveDb()->queryOne($execSql, $binds);
    }/*}}}*/

    public static function formatOnlineStats($onlineStatsList)
    {/*{{{*/
        $list = [];
        $item = [];
        foreach ($onlineStatsList as $onlineStats) {
            $item['sid'] = $onlineStats['id'];
            $item['online'] = $onlineStats['online'];
            $list[] = $item;
        }
        return $list;
    }/*}}}*/

    public static function clearOnlineStatusMc($sid)
    {
        $mcClient = self::getMcClient();
        $key = self::getStarOnlineMcKey($sid);
        $mcClient->delete($key);
        return self::getOnlineFromMc($sid);
    }

    public static function getOnlineStatsListFromDb($sidList)
    {/*{{{*/
        //$query = self::find();
        ////$query->select('id as sid, online')
        //$query->select('id, online')
        //    ->where(['in', 'id', $sidList]);
        //return $query;
        $sids = implode(',', $sidList);
        $connection = self::getDb();
        $sql = "SELECT id, online ";
        $sqlFrom = " FROM " . self::tableName();
        $sqlWhere = " WHERE id in ($sids) ";
        $execSql = $sql . $sqlFrom . $sqlWhere;
        return $connection->createCommand($execSql)->queryAll();
    }/*}}}*/

    public static function getStarByCondition($key, $value)
    {/*{{{*/
        //$query = self::find();
        //$query->select('id, wuid, name, avatar, rank, verified, type, followers_count, total_followers_count, tag')
        //    ->where("{$key}=:{$key}", [":$key"=>$value]);
        //return $query->one();
        $sqlSelect = " SELECT id, wuid, name, avatar, rank, verified, type, followers_count, total_followers_count, tag ";
        $sqlFrom = " FROM " . self::tableName();
        $sqlWhere = " WHERE {$key}=:{$key} ";
        $exeSql = $sqlSelect . $sqlFrom . $sqlWhere;
        $binds = [
            ":$key" => $value,
        ];
        return self::getSlaveDb()->queryOne($exeSql, $binds);
    }/*}}}*/

    public static function getStarByWuidFromMc($wuid)
    {/*{{{*/
        $key = self::getStarByWuidMcKey($wuid);
        $mcClient = self::getMcClient();
        $starJson = $mcClient->get($wuid);
        if (false == $starJson) {
            $star = self::getStarByCondition('wuid', $wuid);
            if (empty($star)) {
                return [];
            }
            $starJson = json_encode($star);
            $mcClient->set($key, $starJson, CacheConfig::CACHE_TIME_MC);
        }
        return json_decode($starJson, true);
    }/*}}}*/

    public static function getStarBySidFromMc($sid, $isLocalCache = true)
    {/*{{{*/
        if ($isLocalCache) {
            $localCacheKey = self::getStarInfoLibShmCacheKey($sid);
            $localCacheResult = LibShmCache::get($localCacheKey);
            if ($localCacheResult) {
                return json_decode($localCacheResult, true);
            }
        }
        $key = self::getStarBySidMcKey($sid);
        $mcClient = self::getMcClient();
        $starJson = $mcClient->get($key);
        if (false == $starJson) {
            $star = self::getStarByCondition('id', $sid);
            if (empty($star)) {
                return [];
            }
            $starJson = json_encode($star);
            $mcClient->set($key, $starJson, CacheConfig::CACHE_TIME_MC);
        }
        if($localCacheKey)
        {
            $shmCacheTime = \apps\classes\ShmCacheTime::get('star_local_cache');
            LibShmCache::set($localCacheKey, $starJson, $shmCacheTime);
        }
        return json_decode($starJson, true);
    }/*}}}*/

    public static function getStarByWuidMcKey($wuid)
    {/*{{{*/
        return CacheConfig::PREFIX_MC . ':' . CacheConfig::STARFANTUAN_MC . ':' . CacheConfig::STAR_BY_WUID_MC . $wuid;
    }/*}}}*/

    public static function getStarBySidMcKey($sid)
    {/*{{{*/
        return CacheConfig::PREFIX_MC . ':' . CacheConfig::STARFANTUAN_MC . ':' . CacheConfig::STAR_BY_SID_MC . $sid;
    }/*}}}*/

    public function clearCache($str, $value)
    {/*{{{*/
        $data = [];
        $str = ucfirst($str);
        $redisClient = self::getClusterRedisClient();
        $getRediskeyFun = 'getStarBy' . $str . 'RedisKey';
        $key  = self::$getRediskeyFun($value);
        $data[$key] = $redisClient->DEL($key);
        $getDataFromFromRedisFun = 'getStarBy' . $str . 'FromRedis';
        self::$getDataFromFromRedisFun($value);
        if ($str == 'Sid') {
            $data['online'] = self::clearOnlineStatusMc($value);
        }
        return $data;
    }/*}}}*/

    public static function getInstance()
    {/*{{{*/
        if (!self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }/*}}}*/
}
