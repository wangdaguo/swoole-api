<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;


class TimelineCustomController extends BaseController 
{
    function actionList()
    {
        $this->setProfileSwitch();
        $sid = $this->post('sid');
        if ((int)$sid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }

        $limit = $this->post('pageSize', 10);
        $time = $this->post('time', 0);
        $actionId = $this->post('actionId', 0);


        $shmCacheKey = '';
        if($time == 0)
        {
            $shmCacheKey  = 'fantuan_timeline_shm_' . $sid . '_' . $actionId;
            $this->sendShmCache($shmCacheKey);
        }
        $this->setTrack('getTimelineList-start');
        $list = \apps\models\TimelineCustom::getTimelineCustomFormatList($sid, $limit, $time, $actionId);
        $this->setTrack('getTimelineList-end');
        $data = [];
        $data['list'] = $list;
        $data['ifDoneTask'] = false;
        $shmCacheTime = \apps\classes\ShmCacheTime::get('timeline_custom_time_0', 2);
        $this->send($data, $shmCacheKey, $shmCacheTime);
    }
}
