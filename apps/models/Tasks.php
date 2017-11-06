<?php
namespace apps\models;
use apps\models\BaseModel;
use apps\classes\CacheConfig;
use apps\classes\LibShmCache;
use apps\classes\PubFunction;
use Swoole;


class Tasks extends BaseModel
{
    private static $_instance;

    const NAME_SPACE = "\\starfantuan\\models\\Tasks";
    const CACHE_EXPIRED = 3600;
    const DONE = 1;
    const UNDONE = 0;

    const TYPE0 = 0;//auto run task
    const TYPE1 = 1;//weibo
    const TYPE2 = 2;//topic

    const STATUS_AVAILABLE = 1;
    const STATUS_DISABLED = 0;

    const TASK_NORMAL_SID = 0;

    public static function tableName()
    {/*{{{*/
        return 'task';
    }/*}}}*/

    public static function checkTask($uid, $sid, $taskId)
    {/*{{{*/
        $key = self::formatKey();
        $value = self::formatValue($uid, $sid, $taskId);
        $redisClient = self::getClusterRedisClient();
        return $redisClient->SISMEMBER($key, $value);
    }/*}}}*/

    public static function doTask($uid, $userToken, $sid, $taskId, $isDone = false, $isCheck = true)
    {/*{{{*/
        if ($isCheck) {
            if (self::checkTask($uid, $sid, $taskId)) {
                return false;
            }
        } else {
            if ($isDone) {
                return false;
            }
        }
        $loginUserInfo = User::getUserInfoByUserToken($userToken);
        self::setTrack('User::getUserInfoByUserToken');
        if (!isset($loginUserInfo['province'])) {
            $loginUserInfoJson = json_encode($loginUserInfo);
            return false;
        }
        $province = $loginUserInfo['province'];
        $taskInfo = self::getTaskDataFromMc($taskId);
        self::setTrack('getTaskDataFromMc');
        if (empty($taskInfo)) {
            return false;
        }
        Tasks::doTaskSetCache($uid, $sid, $taskInfo['id']);
        self::setTrack('doTaskSetCache');
        Star::updateStarShine($sid, $taskInfo['shine']);
        self::setTrack('updateStarShine');
        Star::updateStarDetail($uid, $sid, $taskInfo['shine']);
        self::setTrack('updateStarDetail');
        Star::updateStarGeo($sid, $province, $taskInfo['shine']);
        self::setTrack('updateStarGeo');
        return true;
    }/*}}}*/

    public static function doTaskSetCache($uid, $sid, $taskId)
    {/*{{{*/
        $key = self::formatKey();
        $value = self::formatValue($uid, $sid, $taskId);
        $redisClient = self::getClusterRedisClient();
        $leftTime = PubFunction::getRemainSeconds();
        $redisClient->SADD($key, $value);
        return $redisClient->EXPIRE($key, $leftTime);
    }/*}}}*/

    public static function formatKey()
    {/*{{{*/
        //return 'shine:task:done:' . date('Ymd');
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::ACTION_TYPE_STARFANTUAN_TASK_DO . ':' . date('Ymd');
    }/*}}}*/

    public static function formatValue($uid, $sid, $taskId)
    {/*{{{*/
        return "$uid:$sid:$taskId";
    }/*}}}*/

    public static function getTaskListBySidMcKey($sid)
    {/*{{{*/
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::TASK_LIST_MC. ':' . $sid;
    }/*}}}*/

    public static function getTaskDataMcKey($id)
    {/*{{{*/
        return CacheConfig::REDIS_CACHE_PREFIX . ':' . CacheConfig::APPLICATION_TYPE_STARFANTUAN . ':' . CacheConfig::TASK_DATA_MC. ':' . $id;
    }/*}}}*/

    public static function getTaskListLibShmCacheKey($sid)
    {/*{{{*/
        return CacheConfig::PREFIX_LIBSHMCACHE . '_' . CacheConfig::STAR_FANTUAN_LIB . '_' . CacheConfig::TASK_LIST_LIB. '_' . $sid;
    }/*}}}*/

    public static function getTaskListFromMc($sid, $isLocalCache = true)
    {/*{{{*/

        if ($isLocalCache) {
            $localCacheKey = self::getTaskListLibShmCacheKey($sid);
            $localCacheResult = LibShmCache::get($localCacheKey);
            if ($localCacheResult) {
                return json_decode($localCacheResult, true);
            }
        }
        $mcClient = self::getMcClient();
        $key = self::getTaskListBySidMcKey($sid);
        $taskListJson = $mcClient->get($key);
        if (!$taskListJson) {
            $taskList = self::getTaskListFromDb($sid);
            if (empty($taskList)) {
                return [];
            }
            $taskListJson = json_encode($taskList);
            $mcClient->set($key, $taskListJson, CacheConfig::CACHE_TIME_MC);
        }
        if($isLocalCache)
        {
            $shmCacheTime = \apps\classes\ShmCacheTime::get('task_local_cache');
            LibShmCache::set($localCacheKey, $taskListJson, $shmCacheTime);
        }
        return json_decode($taskListJson, true);
    }/*}}}*/

    public static function getTaskList($sid, $type)
    {/*{{{*/
        $taskList = [];
        $taskListBySid = self::getTaskListFromMc($sid);
        self::setTrack('getTaskListFromMc-' . $sid);
        $taskListNormal = self::getTaskListFromMc(self::TASK_NORMAL_SID);
        self::setTrack('getTaskListFromMc-'. self::TASK_NORMAL_SID);
        $taskListMerge = array_merge($taskListBySid, $taskListNormal);
        if (empty($taskListMerge)) {
            return [];
        }
        foreach ($taskListMerge as $key => $task) {
            if (-1 != $type && $type != $task['type']) {
                unset($taskListMerge[$key]);
                continue;
            }
            $taskList[] = $task;
        }
        PubFunction::tdSort($taskList, 'rank', SORT_DESC, 'created', SORT_DESC);
        self::setTrack('format');
        return $taskList;
    }/*}}}*/

    public static function getTaskDataFromMc($id)
    {/*{{{*/
        $mcClient = self::getMcClient();
        $key = self::getTaskDataMcKey($id);
        self::setTrack('getMc-start');
        $taskDataJson = $mcClient->get($key);

        $server = $mcClient->getServerByKey($key);
        self::setTrack('getMc-end=='. json_encode($server));
        if (!$taskDataJson) {
            $taskData = self::getTaskData($id);
            self::setTrack('getDb');
            if (empty($taskData)) {
                return [];
            }
            $taskDataJson = json_encode($taskData);
            $mcClient->set($key, $taskDataJson, CacheConfig::CACHE_TIME_MC);
        }
        return json_decode($taskDataJson, true);
    }/*}}}*/

    public static function getTaskListFromDb($sid, $type = -1)
    {/*{{{*/
        $date = date('Y-m-d H:i:s');
        $sql = "SELECT id, name, title, content, url, `type`, shine, icon, color, rank, created ";
        $sqlFrom = " FROM " . self::tableName();
        $sqlWhere = "  WHERE sid=:sid AND status=:status AND ((start_time <= :date AND end_time >= :date) OR start_time = '0000-00-00 00:00:00' OR end_time = '0000-00-00 00:00:00') ";
        $sqlOrder = " ORDER BY rank DESC, created DESC";

        $binds[":sid"] = $sid;
        $binds[":status"] = self::STATUS_AVAILABLE;
        $binds[":date"] = $date;

        $execSql = $sql . $sqlFrom . $sqlWhere . $sqlOrder;
        return self::getSlaveDb()->queryAll($execSql, $binds);
    }/*}}}*/

    public function getTaskData($id)
    {/*{{{*/
        $sql = "SELECT id, name, title, content, url, `type`, shine, icon, color ";
        $sqlFrom = " FROM " . self::tableName();
        $sqlWhere = " WHERE id = :id ";
        $execSql = $sql . $sqlFrom . $sqlWhere;
        $binds = [
            ":id" => $id,
        ];
        return self::getSlaveDb()->queryOne($execSql, $binds);
    }/*}}}*/

    public function clearCache($sid, $tid)
    {/*{{{*/
        $data = [];
        $mcClient = self::getMcClient();
        $taskListBySidKey = self::getTaskListBySidMcKey($sid);
        $data[$taskListBySidKey] = $mcClient->delete($taskListBySidKey);
        self::getTaskListFromMc($sid);

        $taskDataKey = self::getTaskDataMcKey($tid);
        $data[$taskDataKey] = $mcClient->delete($taskDataKey);
        self::getTaskDataFromMc($tid);
        return $data;
    }/*}}}*/

    public static function getInstance()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public static function runLog($message)
    {/*{{{*/
        $logFileDate = date('Y-m');
        $logDate = date('Y-m-d H:i:s');

        $logFile = Yii::$app->basePath . "/runtime/logs/task_thrift_{$logFileDate}.log";
        error_log($logDate . " " . $message . " \\\\ ", 3, $logFile);
    }/*}}}*/
}
