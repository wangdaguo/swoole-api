<?php
namespace apps\models;
use apps\models\BaseModel;
use apps\classes\CacheConfig;
use Swoole;

class TimelineCustom extends BaseModel
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

    const FILTER_ID = 14057345;

    const LIST_DEFAULT_ACTION = 0;

    public static function tableName()
    {/*{{{*/
        return 'timeline';
    }/*}}}*/

    public static function getMultiTableName($sid)
    {/*{{{*/
        return self::tableName() . '_'  . $sid % 100;
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
            'videourl' => $timelineData['videoUrl'] == 'http://g.cdn.upliveapps.com' ? '' : $timelineData['videoUrl'],
            'template' => $timelineData['template'],
            'time' => $timelineData['time'],
            'action' => $timelineData['action'],
            'msg' => $timelineData['message'],
            'targetId' => $timelineData['targetId'],
            'protocol' => $timelineData['protocol'],
            'resource' => empty($timelineData['resource']) ? [] : $timelineData['resource']
        ];
        //ins
        if ($data['action'] == Star::INS) {
            if($data['content'] == '[]')
            {
                $data['content'] = '';
            }
            if($data['videourl'])
            {
                $data['videourl'] = str_replace('%2F', '/', $data['videourl']);
                $data['videourl'] = str_replace('g.cdn.upliveapps.com', 'g.cdn.upliveapp.com', $data['videourl']);
            }
            if($data['videourl'] && !$data['avatar'])
            {
                $data['avatar'] = 'http://g.cdn.pengpengla.com/starfantuan/knowme/1491965693601.png';
            } else {
                $data['avatar'] = str_replace('thumbnail', 'medium', $data['avatar']);
            }
            if($data['avatar'])
            {
                $data['avatar'] = str_replace('g.cdn.upliveapps.com', 'g.cdn.upliveapp.com', $data['avatar']);
            }

            
        }
        return $data;
    }/*}}}*/

    public static function getTimelineList($sid, $pageSize, $time, $actionId)
    {/*{{{*/
        $limit = $pageSize;
        $idList = [];
        $mcClient = self::getMcClient();
        $timelinelistKey = self::getTimelineCustomListFirstPageMcKey($sid, $actionId);
        if ($time == 0) {
            self::setTrack('getMc->start');
            $timelineListJson = $mcClient->get($timelinelistKey);
            self::setTrack('getMc->end');
            if (!$timelineListJson) {
                self::setTrack('getDb->start');
                $timelineList = self::getListFromDbByCondition($sid, $limit, $time, $actionId);
                self::setTrack('getDb->end');
                if (empty($timelineList)) {
                    return [];
                }
                $timelineListJson = json_encode($timelineList);
                $mcClient->set($timelinelistKey, $timelineListJson, CacheConfig::CACHE_TIME_MC);
            }
            return json_decode($timelineListJson, true);
        } else {
            self::setTrack('getDb->start');
            $result =  self::getListFromDbByCondition($sid, $limit, $time, $actionId);
            self::setTrack('getDb->start');
            return $result;
        }
    }/*}}}*/

    public static function clearTimelineList($sid, $actionId)
    {
        $keys =  [];
        $item = [];
        switch ($actionId) {
        case Star::INS:
        case Star::INS_FOLLOW:
            $item[] = Star::INS;
            $item[] = self::LIST_DEFAULT_ACTION;
            break;
        case Star::ONLINE:
            $item[] = $actionId;
            break;
        default:
            $item[] = self::LIST_DEFAULT_ACTION;
        }
        $logMsg = "sid:{$sid} actionId:{$actionId} item:" . json_encode($item);
        if ($item) {
            $mcClient = self::getMcClient();
            foreach ($item as $tmpActionId) {
                $key = self::getTimelineCustomListFirstPageMcKey($sid, $tmpActionId);
                $result = $mcClient->delete($key);
                $logMsg .= " [" . $key . " => " . print_r($result, true) . "]";
                self::getTimelineList($sid, self::TIMELINT_OFFSET, 0, $tmpActionId);
            }
        }
        return true;
    }

    public static function getTimelineCustomFormatList($sid, $pageSize, $time, $actionId)
    {/*{{{*/
        $list = [];
        $timelineList = self::getTimelineList($sid, $pageSize, $time, $actionId);
        if (isset($timelineList['isNull'])) {
            return $list;
        }
        foreach ($timelineList as $timelineData) {
            $list[] = self::formatTimelineData($timelineData);
        }
        return $list;
    }/*}}}*/

    public static function getTimelineCustomListFirstPageMcKey($sid, $actionId)
    {/*{{{*/
        return CacheConfig::PREFIX_MC . ':' . CacheConfig::STARFANTUAN_MC . ':' . CacheConfig::TIMELINE_LIST_CUSTOM_MC. ':' . $sid . ':' . $actionId;
    }/*}}}*/

    public  static function getListFromDbByCondition($sid, $limit, $time, $actionId)
    {/*{{{*/
        $sql = "SELECT id, template, UNIX_TIMESTAMP(time) as time, message, avatar, videoUrl, title, content, comment, url, level, action, targetId,protocol ";
        $sqlFrom = " FROM " . self::getMultiTableName($sid);
        $sqlWhere = " WHERE sid=:sid ";

        $binds = [
            ':sid' => $sid,
        ];

        switch ($actionId) {
        case Star::ONLINE:
            $sqlWhere .= " AND action = " . Star::ONLINE;
            break;
        case Star::INS:
            $sqlWhere .= " AND action in (" . Star::INS . "," . Star::INS_FOLLOW . ")";
            break;
        default:
            $sqlWhere .= " AND action <> " . Star::ONLINE . " and action <> " . Star::JOURNEY;
            break;
        }

        if ($time > 0) {
            $sqlWhere .= " AND time < FROM_UNIXTIME(:time) ";
            $binds[':time'] = $time;
        }
        $sqlOrder = " ORDER BY time DESC ";
        $sqlLimit = " limit {$limit}";

        $execSql = $sql . $sqlFrom . $sqlWhere . $sqlOrder . $sqlLimit;
        $list = self::getSlaveDb()->queryAll($execSql, $binds);
        $list = self::getTimelineResource($list, $actionId, $sid);
        return $list;

    }/*}}}*/

    public static function getTimelineResource($timelineList, $actionId, $sid)
    {
        $timelineIdArr= [];
        foreach ($timelineList as $timeline)
        {
            if (in_array($timeline['action'] ,[Star::INS, Star::INS_FOLLOW, Star::INS_UPDATE]))
            {
                $timelineIdArr[]= $timeline['id'];
            }
        }
        $timelineResource = [];
        if ($timelineIdArr)
        {
            $timelineResource = TimelineResource::getList($sid, $timelineIdArr);
        }
        foreach ($timelineList as $key => &$item)
        {
            if($item['action'] ==Star::INS)
            {
                if(isset($timelineResource[$item['id']]))
                {
                    $item['resource'] = $timelineResource[$item['id']];
                } else {
                    if($item['avatar'])
                    {
                        $item['avatar'] = str_replace('thumbnail', 'big', $item['avatar']);
                    }
                    if ($item['videoUrl'])
                    {
                        $item['resource'][] = [
                            'timelineId' => $item['id'],
                            'type' => 1,
                            'videourl' => $item['videoUrl'],
                            'imageurl' => $item['avatar'],
                        ];
                    } else {
                        $item['resource'][] = [
                            'timelineId' => $item['id'],
                            'type' => 0,
                            'videourl' => $item['videoUrl'],
                            'imageurl' => $item['avatar'],
                        ];
                    }
                }
            } else {
                $item['resource'] = [];
            }
        }
        return $timelineList;
    }

}
