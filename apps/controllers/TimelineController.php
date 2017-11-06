<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\Timeline;
use apps\models\TimelineStats;

class TimelineController extends BaseController
{
    public function actionList()
    {/*{{{*/
        $this->setProfileSwitch();
        $data=[
            'list' => [],
        ];
        $sid = $this->post('sid');
        $pageSize = intval($this->post('pageSize', 10));
        $time = intval($this->post('time', 0));

        if ((int)$sid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }
        $shmCacheKey = '';
        if($time == 0)
        {
            $shmCacheKey = 'fantuan_timeline_shm_' . $sid;
            $this->sendShmCache($shmCacheKey);
        }

        // 获取更早动态需要传入时间
        $this->setTrack('getTimelineFormatList->start');
        $list = Timeline::getTimelineFormatList($sid, $pageSize, $time);
        $this->setTrack('getTimelineFormatList->end');
        $data['list'] = $list;
        $data['ifDoneTask'] = false;
        $shmCacheTime = \apps\classes\ShmCacheTime::get('timeline_time_0', 2);
        $this->send($data, $shmCacheKey, $shmCacheTime);
    }/*}}}*/

    public function actionStats()
    {/*{{{*/
        $this->setProfileSwitch();
        $data = [
            'actionList' => [],
        ];
        $sid = $this->post('sid');
        $offset = intval($this->post('offset', 0));
        if ((int)$sid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if (0 == $offset) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($offset . " days"));
        }
        $today = date('Y-m-d');

        $shmCacheKey = md5('fantuan_timelineStats_shm_' . $sid. '_' . $date);
        if($today == $date)
        {
            $shmCacheTime = 5;
        } else { 
            $shmCacheTime = 600;
        }
        $this->sendShmCache($shmCacheKey);

        $this->setTrack('getStatsList-start');
        $statsList = TimelineStats::getStatsList($sid, $date);
        $this->setTrack('getStatsList-end');
        $data['actionList'] = $statsList;
        $this->send($data, $shmCacheKey, $shmCacheTime);
    }/*}}}*/

    public function actionMoveData()
    {/*{{{*/
        $data = [
            'isSuccess' => false,
        ];
        $paramKey = $this->post('paramKey');
        $date = $this->post('date', date("YmdHi"));
        $pubKey = md5('sft' . $date . 'timeline');

        $diffKey = substr($pubKey, 0, 7) . substr($pubKey, -1, -8);

        if ($paramKey !== $diffKey) {
            $data['v'] = false;
            $this->send($data);
        }
        $list = $this->post('list');
        if (empty($list)) {
            $data['isSuccess'] = false;
            $data['paramsStatus'] = false;
        } else {
            $result  = Timeline::moveTimeline($list);
            $data['isSuccess'] = $result['ok'];
            $data['result'] = $result;
        }
        $this->send($data);
    }/*}}}*/

    public function actionImage()
    {/*{{{*/
        $data = [
            "img" => '',
        ];
        $imgurl = $this->post('imgurl');
        $type = $this->post('type');
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }
        $imgurl = str_replace('thumbnail', $type, $imgurl);
        $data['img'] = str_replace('medium', $type, $imgurl);
        $this->send($data);
    }/*}}}*/

}

