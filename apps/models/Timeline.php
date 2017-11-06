<?php
namespace apps\models;
use apps\models\BaseModel;
use apps\classes\CacheConfig;
use Swoole;

class Timeline extends BaseModel
{
    const SINGLE_PROCESS = true;
    const REDISCLIENT = 2;

    const REDIS_RPOP_TIMEOUT = 10;

    const RUN_COUNT = 25;
    const PROCESS_COUNT = 5;
    const SYNC_COUNT  = 100;

    const CACHE_EXPIRED = 3600;

    const _SEGMENT_TIME = 1460736000;

    const TABLE_COUNT = 100;

    const TIMELINT_OFFSET = 10;
    const TIMELINE_PAGE = 10;

    const REDIS_TIME_LINE_LEN = 2;

    private static $_allowFork = true;

    public static function getList($uid, $sid, $page, $pageSize, $time, $useCache = true)
    {/*{{{*/
        if ($useCache && $page == 1) {
            $timelineList = self::getLatestCache($sid, $page);
            if ($timelineList) {
                return self::addIsDoTask($timelineList, $uid, $sid);
            }
        }

        $sql = 'select 
            template, UNIX_TIMESTAMP(time) as time, message, avatar, title, content, comment, url, level, action
            from ' . self::tableName($sid) . '
            where sid = :sid ';
        $binds = [
            ':sid' => $sid,
        ];
        if ($time > 0) {
            $sql .= ' and time <= FROM_UNIXTIME(:time) ';
            $binds[':time'] = $time;
        }

        $offset = ($page - 1) * $pageSize;

        $sql .= 'order by time desc limit :offset , :pageSize';
        $binds[':offset'] = $offset;
        $binds[':pageSize'] = $pageSize;
        $timelineList = self::getDb()->createCommand($sql, $binds)->queryAll();

        foreach ($timelineList as $key => $timeline) {
            if ($timeline['action'] == Star::INS_UPDATE && $timeline['time'] > self::_SEGMENT_TIME) {
                $timelineList[$key]['middle_image'] = str_replace('small', 'middle', $timeline['avatar']);
            }
        }
        if ($timelineList && $useCache && $page == 1) {
            self::setLatestCache($sid, $page, $timelineList);
        }
        return self::addIsDoTask($timelineList, $uid, $sid);
    }/*}}}*/

    public static function addIsDoTask($timeline, $uid, $sid)
    {/*{{{*/
        // 每日打卡任务id
        $dayTaskId = \Yii::$app->params['dayTaskId'];
        $isDone = Tasks::checkTask($uid, $sid, $dayTaskId);
        return [
            'timeline' => $timeline,
            'isDoneTask' => $isDone
        ];
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'timeline';
    }/*}}}*/

    public static function getMultiTableName($sid)
    {/*{{{*/
        return self::tableName() . '_' . $sid % 100;
    }/*}}}*/

    public static function getInfoBySidAndUrl($sid, $url)
    {/*{{{*/
        $sql = 'select sid, time, title, content, comment, url
            from ' . self::tableName() . '
            where sid = :sid and url = :url';
        $binds[':sid'] = $sid;
        $binds[':url'] = $url;
        return self::getDb()->createCommand($sql, $binds)->queryOne();
    }/*}}}*/

    public static function getLatestTimelineByAction($sid, $action)
    {/*{{{*/
        $sql = 'select sid, time, title, content, comment, url
            from ' . self::tableName() . '
            where sid = :sid and action = :action order by time desc';
        $binds[':sid'] = $sid;
        $binds[':action'] = $action;
        return self::getDb()->createCommand($sql, $binds)->queryOne();
    }/*}}}*/

    public static function getLatestCache($sid, $page)
    {/*{{{*/
        $cacheKey = self::getLatestCacheKey($sid, $page);
        return self::getMemcachedClient()->get($cacheKey);
    }/*}}}*/

    public static function setLatestCache($sid, $page, $data)
    {/*{{{*/
        $cacheKey = self::getLatestCacheKey($sid, $page);
        return self::getMemcachedClient()->set($cacheKey, $data);
    }/*}}}*/

    public static function getLatestCacheKey($sid, $page)
    {/*{{{*/
        return 'fantuan_timeline_latest_' . $sid . '_' . $page;
    }/*}}}*/

    public static function getTimelineListbak($sid, $pageSize, $time)
    {/*{{{*/
        $list = [];
        $timelineIdList = self::getTimelineIdList($sid, $pageSize, $time);
        if (empty($timelineIdList) || (0 == ($timelineIdList[0]) && 1 == sizeof($timelineIdList))) {
            return [];
        }
        foreach ($timelineIdList as $key => $timelineId) {
            $timelineData = self::getTimelineDataFromRedis($sid, $timelineId);
            if (isset($timelineData['isNull']) && 1 == sizeof($timelineData)) {
                continue;
            }
            $list[] = self::formatTimelineData($timelineData);
        }
        return $list;
    }/*}}}*/

    public static function formatTimelineData($timelineData)
    {/*{{{*/
        $data = [
            'id' => $timelineData['id'],
            'comment' => $timelineData['comment'],
            'title' => $timelineData['title'],
            'wburl' => $timelineData['url'],
            'level' => $timelineData['level'],
            'content' => $timelineData['content'],
            'avatar' => $timelineData['avatar'],
            'videourl' => $timelineData['videoUrl'],
            'template' => $timelineData['template'],
            'time' => $timelineData['time'],
            'action' => $timelineData['action'],
            'msg' => $timelineData['message'],
        ];
        //行程
        if ($data['action'] == 8000) {
            $data['avatar'] = str_replace('http://m.sfantuan.com/images/journey.jpg', 'http://g.cdn.pengpengla.com/oauthgame/knowme/journey.jpg', $data['avatar']);
        } else if ($data['action'] == 9000) {
            //ins
            if($data['videourl'] && !$data['avatar'])
            {
                $data['avatar'] = 'http://g.cdn.pengpengla.com/starfantuan/knowme/1491965693601.png';
            } else {
                $data['avatar'] = str_replace('thumbnail', 'medium', $data['avatar']);
            }
        }
        return $data;
    }/*}}}*/

    public static function getTimelineList($sid, $pageSize, $time)
    {/*{{{*/
        $limit = $pageSize;
        $idList = [];
        $mcClient = self::getMcClient();
        $timelinelistKey = self::getTimelineListFirstPageMcKey($sid);
        if ($time == 0) {
            self::setTrack('getMc->start');
            $timelineListJson = $mcClient->get($timelinelistKey);
            self::setTrack('getMc->start');
            if (!$timelineListJson) {
                self::setTrack('getDb->start');
                $timelineList = self::getListFromDbByCondition($sid, $limit, $time);
                self::setTrack('getDb->end');
                if (empty($timelineList)) {
                    $timelineList = [
                        'isNull' => true,
                    ];
                }
                $timelineListJson = json_encode($timelineList);
                $mcClient->set($timelinelistKey, $timelineListJson, CacheConfig::CACHE_TIME_MC);
            }
            return json_decode($timelineListJson, true);
        } else {
            self::setTrack('getDb->start');
            $list = self::getListFromDbByCondition($sid, $limit, $time);
            self::setTrack('getDb->end');
            return $list;
        }
    }/*}}}*/

    public static function getTimelineFormatList($sid, $pageSize, $time)
    {/*{{{*/
        $list = [];
        $timelineList = self::getTimelineList($sid, $pageSize, $time);
        if (isset($timelineList['isNull'])) {
            return $list;
        }
        foreach ($timelineList as $timelineData) {
            $list[] = self::formatTimelineData($timelineData);
        }
        return $list;
    }/*}}}*/

    public static function ifDoTask($uid, $sid, $dayTaskId)
    {/*{{{*/
        // 每日打卡任务id
        return Tasks::checkTask($uid, $sid, $dayTaskId);
    }/*}}}*/

    public static function getTimelineListFirstPageMcKey($sid)
    {/*{{{*/
        return CacheConfig::PREFIX_MC . ':' . CacheConfig::STARFANTUAN_MC . ':' . CacheConfig::TIMELINE_LIST_MC. ':' . $sid;
    }/*}}}*/

    public static function getOldTimelineListRedisKey($sid)
    {/*{{{*/
        return 'lts:' . $sid;
    }/*}}}*/

    public static function getListFromDbByCondition($sid, $limit, $time)
    {/*{{{*/
        $sql = "SELECT id, template, UNIX_TIMESTAMP(time) as time, message, avatar, videoUrl, title, content, comment, url, level, action, protocol ";
        $sqlFrom = " FROM " . self::getMultiTableName($sid);
        $sqlWhere = " WHERE sid=:sid ";


        $binds = [
            ':sid' => $sid,
        ];

        if ($time > 0) {
            $sqlWhere .= " AND time < FROM_UNIXTIME(:time) ";
            $binds[':time'] = $time;
        }
        $sqlOrder = " order by time desc ";
        $sqlLimit = " limit {$limit}";

        $execSql = $sql . $sqlFrom . $sqlWhere . $sqlOrder . $sqlLimit;
        return self::getSlaveDb()->queryAll($execSql, $binds);
    }/*}}}*/

    public static function getDataFromDbBySid($sid, $page, $time = 0)
    {/*{{{*/
        $connection = self::getDb();

        $sql = "SELECT id, template, UNIX_TIMESTAMP(time) as time, message, avatar, title, content, comment, url, level, action ";
        $sqlFrom = " FROM " . self::getMultiTableName($sid);
        $sqlWhere = " WHERE id=:id ";
        $sqlOrder = " ORDER BY time DESC ";
        $sqlLimit = " limit 10";

        $binds = [
            ":id" => $id,
        ];
        $execSql = $sql . $sqlFrom . $sqlWhere . $sqlOrder . $sqlLimit;
        return $connection->createCommand($execSql, $binds)->queryOne();
    }/*}}}*/

    public static function getOnlineList($sid, $startDate, $endDate)
    {/*{{{*/
        $list = [];
        $onlineList = self::getOnlinesList($sid, $startDate, $endDate);
        if (empty($onlineList)) {
            return [];
        }
        return self::formatOnlinesList($onlineList);
    }/*}}}*/

    public static function formatOnlinesList($onlineList)
    {/*{{{*/
        $list = [];
        $item = [];
        $lastItem = array_pop($onlineList);
        foreach ($onlineList as $online) {
            if ($online['comment']) {
                $item['time'] = date('Y-m-d H:i', strtotime($online['time']));
                $item['duration'] = ceil((int)$online['comment'] / 60);
                $list[] = $item;
            }
        }
        if ($lastItem['comment']) {
            $item['time'] = date('Y-m-d H:i', strtotime($lastItem['time']));
            $item['duration'] = ceil((int)$lastItem['comment'] / 60);
            $list[] = $item;
        } else {
            $item['time'] = date('Y-m-d H:i', strtotime($lastItem['time']));
            $item['duration'] = 0;
            $list[] = $item;
        }
        return $list;
    }/*}}}*/

    public static function getOnlinesList($sid, $startDate, $endDate)
    {/*{{{*/
        $sql = "SELECT id, time, comment ";
        $sqlFrom = " FROM " . self::getMultiTableName($sid);
        $sqlWhere = " WHERE sid=:sid AND action=:action AND time>=:startDate AND time<:endDate ";
        $binds = [
            ":sid" => $sid,
            ":action" => Star::ONLINE,
            ":startDate" => $startDate,
            ":endDate" => $endDate,
        ];
        $execSql = $sql . $sqlFrom . $sqlWhere;
        return self::getSlaveDb()->queryAll($execSql, $binds);
    }/*}}}*/

    public static function getTimelineDataBySidAndUrlRedisKey($sid)
    {/*{{{*/
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::ACTION_TYPE_STARFANTUAN_TIMELINE_BY_SID_AND_URL. ':' . $sid;
    }/*}}}*/

    public static function getTimelineDataBySidAndActionRedisKey($sid, $action)
    {/*{{{*/
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::ACTION_TYPE_STARFANTUAN_TIMELINE_BY_SID_AND_ACTION. ':' . $sid . ':' . $action;
    }/*}}}*/

    public static function getTimelineDataBySidAndUrl($sid, $url)
    {/*{{{*/
        $redisClient = self::getClusterRedisClient();
        $timelineData = self::getTimelineQueryByKeyValue($sid, 'url', '');
        if (empty($timelineData)) {
            return [];
        } else {
            return $timelineData;
        }
    }/*}}}*/

    public static function getTimelineDataBySidAndActionFromRedis($sid, $action)
    {/*{{{*/
        $redisClient = self::getClusterRedisClient();
        $key = self::getTimelineDataBySidAndActionRedisKey($sid, $action);
        if (!$redisClient->EXISTS($key)) {
            $timelineData = self::getTimelineQueryByKeyValue($sid, 'action', $action);
            if (empty($timelineData)) {
                $redisClient->HSET($key, 'isNull', true);
            } else {
                $redisClient->HMSET($key, $timelineData);
            }
        }
        $redisClient->EXPIRE($key, CacheConfig::REDIS_CACHE_TIME);
        return $redisClient->HGETALL($key);
    }/*}}}*/

    public static function getTimelineQueryByKeyValue($sid, $key, $value)
    {/*{{{*/
        $sql = "SELECT sid, time, title, content, comment, url ";
        $sqlFrom = " FROM " . self::getMultiTableName($sid);
        $sqlWhere = " WHERE sid=:sid AND {$key}=:{$key} ";
        $sqlOrder = " ORDER BY time DESC";

        $binds = [
            ":sid" => $sid,
            ":{$key}" => $value,
        ];
        $execSql = $sql . $sqlFrom . $sqlWhere . $sqlOrder;
        return self::getSlaveDb()->queryOne($execSql, $binds);
    }/*}}}*/

    public static function clearIdListCache($sid)
    {/*{{{*/
        $redisClient = self::getClusterRedisClient();
        $key = self::getTimelineIdListRedisKey($sid);
        return $redisClient->DEL($key);
    }/*}}}*/

    public static function saveDataForTest($list)
    {/*{{{*/
        $connection = self::getDb();
        $newList = self::handleDataForTest($list);
        $transaction = $connection->beginTransaction();
        try {
            $connection->createCommand()->batchInsert('timeline', ['id', 'sid', 'action', 'level', 'template', 'time', 'message', 'avatar', 'title', 'content', 'comment', 'url', 'push', 'created'], $list)->execute();
            foreach ($newList as $sid => $arr) {
                self::setOldTimeLineListToRedis($sid);
                $table = 'timeline' . $sid % 100;
                $connection->createCommand()->batchInsert($table, ['id', 'sid', 'action', 'level', 'template', 'time', 'message', 'avatar', 'title', 'content', 'comment', 'url', 'push', 'created'], $arr)->execute();
                self::clearIdListCache($sid);
                $idList = self::getTimelineIdList($sid, self::TIMELINT_OFFSET, 0);
                if ((0 == ($timelineIdList[0]) && 1 == sizeof($timelineIdList)) || empty($timelineIdList)) {
                    continue;
                }
                foreach ($idList as $id) {
                    self::getTimelineDataFromRedis($sid, $id);
                }
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }/*}}}*/


    public static function setOldTimeLineListToRedis($sid)
    {/*{{{*/
        $redisClient = self::getSingleRedisClient();
        $key = self::getOldTimelineListRedisKey($sid);
        $timelineList = self::getListFromDbByCondition($sid, self::TIMELINT_OFFSET, 0);
        $timelineListJson = json_encode($timelineList);
        $redisClient->SET($key, $timelineListJson);
        $redisClient->EXPIRE($key, CacheConfig::REDIS_CACHE_TIME);
    }/*}}}*/

    public static function handleDataForTest($list)
    {/*{{{*/
        $data = [];
        foreach ($list as $k => $v) {
            $data[$v['sid']] = $v;
        }
        return $data;
    }/*}}}*/

    public static function syncTimelineSourceRedisKey()
    {
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::SYNC_TIMELINE . ':' . 0;
    }

    public static function syncTimelineCurrentRedisKey()
    {
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::SYNC_TIMELINE . ':' . 1;
    }

    public static function handleDataForPop()
    {/*{{{*/
        $data = [];
        $currentKey = self::syncTimelineCurrentRedisKey();
        $i = 0;
        while (1) {
            $i++;
            try {
                $redisClient = self::getCustomRedisClient();
                $count = $redisClient->SCARD($currentKey);
                if (0 != $count) {
                    $pop = $redisClient->SPOP($currentKey);
                    self::popLog(date("Y-m-d H:i:s") . "\t" . $pop . "\t");
                    if (null == $pop) {
                        return true;
                    }
                    $id = $pop;
                    self::syncDataContro($id);
                    return true;
                } else {
                    return true;
                    //$pid = getmypid();
                    //posix_kill($pid, SIGTERM);
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if (isset($pop)) {
                    self::addExceptionIdToSourceRedis($pop);
                    self::runLog(" ****Exception RPOP, return {$pop} to source redis list, ExceptionMsg:{$msg}");
                }
                self::runLog(" ****Exception RPOP, no catch pop return, ExceptionMsg:{$msg}");
                posix_kill(getmypid(), SIGTERM);
            }
        }
        return true;
    }/*}}}*/

    public static function syncDataContro($id)
    {
        $query = self::getDbFromOldDbById($id);
        if ($query->exists()) {
            self::addDataToNewDb($query);
        } else {
            $selectLog = ' ***** source data no data by id = ' . $id;
            self::runLog($selectLog);
        }
        return true;
    }

    public static function getDbFromOldDbById($id)
    {/*{{{*/
        $query = self::find();
        $query->select('*')
            ->where('id=:id', [':id' => $id]);
        return $query;
    }/*}}}*/

    public static function addDataToNewDb($oldQuery)
    {/*{{{*/
        $old = $oldQuery->one();
        $oldData = $old->toArray();
        $connection = self::getDb();
        $tableName = self::getMultiTableName($old->sid);
        $transaction = $connection->beginTransaction();
        $values  = '';
        $fields = '';
        $binds = [];
        foreach ($oldData as $field => $value) {
            $values .= ":{$field},";
            $fields .= "{$field},";
            $key = ':' . $field;
            $binds[$key] = $value;
        }
        $values = trim($values, ',');
        $fields = trim($fields, ',');
        //echo "$values ****================\n";
        try {
            $sql = "INSERT INTO " . $tableName . " ($fields) VALUES($values) ON DUPLICATE KEY UPDATE message= :message, avatar= :avatar, title= :title, content= :content, comment= :comment, url= :url, push= :push, videoUrl=:videoUrl";
            $exeResult = $connection->createCommand($sql, $binds)->execute();
            $exeMessage = 'success sid: ' . $old->sid . ' id:' . $old->id . ' exeResult:' . $exeResult;
            self::syncLog($exeMessage);
            $transaction->commit();
            self::addIdToTimelineListRedis($old->sid, $old->action);
            if ($old->action == 8000) {
                self::clearCacheForJava($old->sid, strtotime($old->time), $old->title);
            }
        } catch (\Exception $e) {
            self::addExceptionIdToSourceRedis($old->id);
            $msg = $e->getMessage();
            self::runLog(date('Y-m-d H:i:s') . " ****Exception Insert Mutiple Table, return id = {$old->id} to source redis list, ExceptionMsg:{$msg}");
            $transaction->rollBack();
            posix_kill(getmypid(), SIGTERM);
        }
    }/*}}}*/

    public static function addExceptionIdToSourceRedis($id)
    {
        $sourceKey = self::syncTimelineSourceRedisKey();
        $redisClient = self::getSingleRedisClient();
        $redisClient->ZADD($sourceKey, time(), $id);
    }

    public static function addIdToTimelineListRedis($sid, $action)
    {/*{{{*/
        $mcClient = self::getMcClient();
        $timelinelistKey = self::getTimelineListFirstPageMcKey($sid);
        $mcClient->delete($timelinelistKey);
        self::getTimelineList($sid, self::TIMELINE_PAGE, 0);

        TimelineCustom::clearTimelineList($sid, $action);

        $redisClient = self::getClusterRedisClient();
        $timelineDataBySidAndActionKey = self::getTimelineDataBySidAndActionRedisKey($sid, $action);
        $redisClient->DEL($timelineDataBySidAndActionKey);
        self::getTimelineDataBySidAndActionFromRedis($sid, $action);
    }/*}}}*/

    public static function clearCacheForJava($sid, $time, $title)
    {
        try {
            $result = CacheTClient::getInstance()->clearStrokeCache($sid, $time, $title);
            $logMsg = "clear success sid:{$sid} time:{$time} title:{$title}";
        } catch (\Exception $e) {
            $logMsg = "clear failed sid:{$sid} time:{$time} title:{$title}, exception:" . $e->getMessage();
        }
        self::clearCacheForJavaLog($logMsg);
        return true;
    }

    public static function syncTimeline()
    {/*{{{*/
        try {
            $sourceKey = self::syncTimelineSourceRedisKey();
            $currentKey = self::syncTimelineCurrentRedisKey();
            $redisClient = self::getSingleRedisClient();
            //$redisClient = self::getClusterRedisClient();
            $idList = $redisClient->ZRANGE($sourceKey, 0, self::SYNC_COUNT - 1);
            self::syncLog(date("Y-m-d H:i:s") . ' read source id list: ' . json_encode($idList));
            if (empty($idList)) {
                return [];
            }
            //var_dump($idList, $sourceKey, $currentKey);

            $idsStr = '';
            array_unshift($idList, $sourceKey);
            $zremCount = call_user_func_array([$redisClient, "ZREM"], $idList);
            self::syncLog(date("Y-m-d H:i:s") . ' rm source id list: ' . $zremCount);
            unset($idList[0]);
            //var_dump($sourceKey, $currentKey, $zremCount);

            //$cluserRedisClient = self::getClusterRedisClient();
            $cluserRedisClient = self::getCustomRedisClient();
            array_unshift($idList, $currentKey);
            $saddCount = call_user_func_array([$cluserRedisClient, "SADD"], $idList);
            if (0 == $cluserRedisClient->SCARD($currentKey)) {
                self::syncLog(date("Y-m-d H:i:s") . ' sync source id list to current list: []');
                return [];
            }
            $currentItems = $cluserRedisClient->SMEMBERS($currentKey);
            self::syncLog(date("Y-m-d H:i:s") . 'current items:' . json_encode($currentItems));
            self::syncLog(date("Y-m-d H:i:s") . ' sync source id list to current list: ' . json_encode($idList));
            //return true;
        } catch (\Exception $e) {
            return [];
            self::syncLog(date("Y-m-d H:i:s") . ' sync exception: ');
            PubFunction::dayLog(date("Y-m-d H:i:s") . " " . $e->getMessage() . PHP_EOL);
        }
    }/*}}}*/

    public static function getCurrentRedisCount()
    {/*{{{*/
        $currentKey = self::syncTimelineCurrentRedisKey();
        $redisClient = self::getCustomRedisClient();
        return $redisClient->SCARD($currentKey);
    }/*}}}*/

    public static function getCustomRedisClient()
    {
        if (self::REDISCLIENT == 1) {
            return self::getSingleRedisClient();
        }
        return self::getClusterRedisClient();
    }

    public static function testAddData()
    {/*{{{*/
        $sourceKey = self::syncTimelineSourceRedisKey();
        $redisClient = self::getSingleRedisClient();
        //$redisClient->DEL($sourceKey);
        $members = $redisClient->ZRANGE($sourceKey, 0, -1);
        foreach (range(1, 100) as $i) {
            $redisClient->ZADD($sourceKey, time(), $i);
        }
        $members = $redisClient->ZRANGE($sourceKey, 0, -1);
    }/*}}}*/

    public function beginProcessing($data)
    {/*{{{*/
        $pids = array();
        $parent_pid = getmypid();
        $pids = array();
        try {
            $currentRedisCount = Timeline::getCurrentRedisCount();
            if (0 >= $currentRedisCount) {
                self::syncTimeline();
            }
            if (0 < $currentRedisCount) {
                if (self::SINGLE_PROCESS) {
                    Timeline::handleDataForPop();
                    return true;
                } else {
                    $maxProcess = $currentRedisCount > self::PROCESS_COUNT ? self::PROCESS_COUNT : $currentRedisCount;
                    for ($i = 0; $i < $maxProcess; $i++) {
                        $pids[] = pcntl_fork();
                    }
                    if (getmypid() == -1) {
                        exit(0);
                    } if (getmypid() == $parent_pid) {
                        while (count($pids) > 0) {
                            $pid = pcntl_waitpid(-1, $status, 'WNOHANG');
                            /* Hunt down and remove pid entry */
                            foreach ($pids as $key => $tpid) {
                                if ($pid == $tpid) {
                                    $oldCount  = sizeof($pids);
                                    unset($pids[$key]);
                                    $newCount  = sizeof($pids);
                                    //posix_kill($pid, SIGTERM);
                                }
                            }
                        }
                        return true;
                    } else {
                        /* Children threads */
                        Timeline::handleDataForPop();
                        $pid = getmypid();
                        posix_kill($pid, SIGTERM);
                    }
                }
            } else {
                return true;
            }
        } catch (\Exception $e) {
            self::runLog(date("Y-m-d H:i:s") . " ****Exception " . $e->getMessage() . "\t");
            $pid = getmypid();
            posix_kill($pid, SIGTERM);
        }
    }/*}}}*/

    public static function moveTimeline($list)
    {/*{{{*/
        $data = [];
        $connection = self::getMasterDb();
        $data['msg'] = '';
        $data['count'] = 0;
        $data['ok'] = false;
        foreach ($list as $sid => $idList) {
            $tableName = self::getMultiTableName($sid);
            $choiceSql = 'select * from ' . $tableName . ' 
                where sid = :sid and id in (:id)';
            $binds = [
                ':sid' => $sid,
                ':id' => implode(',', $idList)
            ];

            $timelineList = $connection->queryAll($choiceSql, $binds);


            $ids = implode(',', $idList);
            $connection->beginTransaction();
            try {
                $sql = "DELETE FROM " . $tableName . " where id in ($ids)";
                $exeResult = $connection->execute($sql);
                $data['msg'] = 'delete sid:' . $sid . ', delete idlist:' . json_encode($idList);
                $data['count'] = $exeResult;
                $data['ok'] = true;
                $connection->commit();
                foreach ($timelineList as $timelineData) {
                    self::clearCacheForDelete($timelineData);
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $data['msg'] = $msg;
                $data['count'] = 0;
                $data['ok'] = false;
                $connection->rollBack();
            }
            return $data;
        }

    }/*}}}*/

    public static function clearCacheForDelete($oldData)
    {/*{{{*/
        $mcClient = self::getMcClient();
        $timelineKey = self::getTimelineListFirstPageMcKey($oldData['sid']);
        $mcClient->delete($timelineKey);
        self::getTimelineList($oldData['sid'], self::TIMELINE_PAGE, 0);

        TimelineCustom::clearTimelineList($oldData['sid'], $oldData['action']);
        $redisClient = self::getClusterRedisClient();
        $timelineDataBySidAndActionKey = self::getTimelineDataBySidAndActionRedisKey($oldData['sid'], $oldData['action']);
        $redisClient->DEL($timelineDataBySidAndActionKey);
        self::getTimelineDataBySidAndActionFromRedis($oldData['sid'], $oldData['action']);
    }/*}}}*/

    public static function syncLog($message)
    {/*{{{*/
        $date = date('Y-m-d');
        $dir = date('Ym');
        $logFile = "sync_timeline_{$date}.log";
        Tools::log($message, $logFile, $dir);
    }/*}}}*/

    public static function runLog($message)
    {/*{{{*/
        $date = date('Y-m-d');
        $dir = date('Ym');
        $logFile = "run_timeline_{$date}.log";
        Tools::log($message, $logFile, $dir);
    }/*}}}*/

    public static function popLog($message)
    {/*{{{*/
        $date = date('Y-m-d');
        $dir = date('Ym');
        $logFile = "pop_timeline_{$date}.log";
        Tools::log($message, $logFile, $dir);
    }/*}}}*/

    public static function clearCacheForJavaLog($message)
    {/*{{{*/
        $date = date('Y-m-d');
        $dir = date('Ym');
        $logFile = "clear_java_timeline_{$date}.log";
        Tools::log($message, $logFile, $dir);
    }/*}}}*/

    public static function saveMutilData($data, $resourceArr = [])
    {/*{{{*/
        $sid = $data['sid'];
        if(!isset($data['push']))
        {
            $data['push'] = 0;
        }
        $tableColumns = ['url', 'sid', 'action', 'level', 'template', 'time', 'message', 'avatar', 'title','content','comment', 'videoUrl', 'targetId','push'];

        $keys = [];
        $values = [];
        $binds = [];

        $transaction = self::getDb()->beginTransaction();
        try{
            foreach ($tableColumns as $column) {
                if (!empty($data[$column])) {
                    $keys[] = $column;
                    $values[] = ":$column";
                    $binds[":$column"] = $data[$column];
                }
            }

            $sql = 'insert into ' . self::tableName() . '(' .
                implode(',', $keys) . ')values('.
                implode(',', $values) . ')';

            $result = self::getDb()->createCommand($sql, $binds)->execute();
            $id = 0;
            if ($result) {
                $id = self::getDb()->getLastInsertID();
                TimelineResource::saveData($sid, $id, $resourceArr);
                self::addExceptionIdToSourceRedis($id);
            }
            $transaction->commit();
            return $id;
        } catch (\Exception $e) {
            $transaction->rollback();
            return 0;
        }
    }/*}}}*/ 

    // 发送微博统计 
    public static function timelineStat($data)
    {/*{{{*/
        $sid = $data['sid'];
        $action = $data['action'];
        $date = date('Y-m-d', strtotime($data['time']));
        $key = sprintf('tl:%s:%s', $date, $sid);
        $redisClient = self::getSingleRedisClient();
        $redisClient->HINCRBY($key, $action, 1);
        $redisClient->expire($key, Tools::getRemainSeconds());
    }/*}}}*/
}
