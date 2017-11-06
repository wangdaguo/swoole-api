<?php
namespace apps\controllers;
use Swoole;
use apps\models\Star;
use apps\models\Timeline;
use apps\classes\ErrorConfig;
class StarController extends BaseController
{
    public function actionGetShineList()
    {/*{{{*/
        $this->setProfileSwitch();
        $data = [
            'mapDatas' => [],
        ];
        $page = $this->post('page', 1);
        $count = $this->post('count', 10);
        if ($this->uid <= 0) {
            $this->sendError(ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH);
        }
        $shmCacheKey = '';
        if ($page == 1) 
        {
            $shmCacheKey = 'fantuan_shinelist_shm';
            $this->sendShmCache($shmCacheKey);
        }
        $this->setTrack('Star::getShineList');
        $result = Star::getShineList($this->uid, $page, $count);
        $this->setTrack('Star::getShineList');
        $data['mapDatas'] = $result;
        $shmCacheTime = \apps\classes\ShmCacheTime::get('fantuan_shinelist_shm_page_1');
        $this->send($data, $shmCacheKey, $shmCacheTime);
    }/*}}}*/

    // 明星在线动态
    public function actionOnline()
    {/*{{{*/
        $this->setProfileSwitch();
        $data = [
            "star" => [],
        ];
        $sids = $this->post('sids');

        $sidList = @json_decode($sids, true);
        if (!is_array($sidList) || empty($sidList)) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }
        $shmCacheKey = '';
        if (count($sidList) == 1)
        {
            $shmCacheKey = 'fantuan_online_shm_' . $sidList[0];
            $this->sendShmCache($shmCacheKey);
        }
        $onlineStatsList = Star::getOnlineStats($sidList);
        $this->setTrack('Star::getOnlineStats');
        $data["star"] = $onlineStatsList;
        $this->send($data, $shmCacheKey, 2);
    }/*}}}*/

    public function actionOnlines()
    {/*{{{*/
        $this->setProfileSwitch();
        $sid = $this->post('sid');
        $data = [
            "sid" => $sid,
            "onlineList" => [],
        ];
        $offset = intval($this->post('offset', 0));
        if ((int)$sid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }

        $today = date('Y-m-d 00:00:00');
        if (0 == $offset) {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 00:00:00', strtotime("+1 days"));
        } else {
            $startDate = date('Y-m-d', strtotime($offset . " days"));
            $endDate = date('Y-m-d', strtotime($offset + 1 . " days"));
        }
        $shmCacheKey = md5('fantuan_onlines_shm_' . $sid. '_' . $startDate . '_' . $endDate);
        if($today == $startDate)
        {
            $shmCacheTime = 5;
        } else { 
            $shmCacheTime = 600;
        }
        $this->sendShmCache($shmCacheKey);

        $this->setTrack('Timeline::getOnlineList->start');
        $onlineList = Timeline::getOnlineList($sid, $startDate, $endDate);
        $this->setTrack('Timeline::getOnlineList->end');
        $data = [
            'sid' => $sid,
            'lineList' => $onlineList
        ];
        $this->send($data, $shmCacheKey, $shmCacheTime);
    }/*}}}*/

    public function actionClearOnlineCache()
    {
        $sid = $this->post('sid');
        Star::clearOnlineStatusMc($sid);
        $this->send([]);
    }
}
