<?php
namespace apps\controllers;
use Swoole;
use apps\models\Star;
use apps\classes\ErrorConfig;
use apps\classes\PubFunction;
use apps\models\Shine;
use apps\models\TaskRankWeek;
use apps\models\TaskRankMonth;

class ShineController extends BaseController
{
    public function actionIndex()
    {/*{{{*/

        $this->setProfileSwitch();
        $data = [
            "mapDatas" => [],
        ];
        $sid = $this->post('sid');
        $count = $this->post('count', 3);
        if ((int)$sid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $data);
        }

        $shmCacheKey = 'fantuan_shineindex_shm_' . $sid;
        $this->sendShmCache($shmCacheKey);

        $result = Shine::getShines($this->uid, $sid, $count);
        $this->setTrack('Shine::getShines');
        $data['mapDatas'] = $result;
        $shmCacheTime = \apps\classes\ShmCacheTime::get('shine_index');
        $this->send($data, $shmCacheKey, $shmCacheTime);
    } /*}}}*/

    public function actionWeek()
    {
        $this->setProfileSwitch();

        $dateWeekAgo = date('Y-m-d', (time() - 86400 * 7));
        $weekArr = PubFunction::getWeek($dateWeekAgo);
        $week = $weekArr[0] . '-' . $weekArr[1];

        $shmCacheKey = md5('fantuan_task_rank_week_shm_' . $week);
        $this->sendShmCache($shmCacheKey);

        $data = [];
        $result = TaskRankWeek::getList($week);
        $data['list'] = $result;
        $data['date'] = self::formatDate($weekArr[0]) . '-' . self::formatDate($weekArr[1]);
        $this->setTrack('getList');
        $this->send($data, $shmCacheKey, 10);
    }
    public function actionMonth()
    {
        $this->setProfileSwitch();

        $dateMonthAgo = date('Y-m-d',strtotime('-1 month'));
        $monthArr = PubFunction::getMonth($dateMonthAgo);
        $month = $monthArr[0] . '-' . $monthArr[1];

        $shmCacheKey = md5('fantuan_task_rank_month_shm_' . $month);
        $this->sendShmCache($shmCacheKey);

        $data = [];
        $result = TaskRankMonth::getList($month);
        $data['list'] = $result;
        $data['date'] = self::formatDate($monthArr[0]) . '-' . self::formatDate($monthArr[1]);
        $this->setTrack('getList');
        $this->send($data, $shmCacheKey, 10);
    }

    public static function formatDate($date)
    {
        return date('Y/n/j', strtotime($date));
    }

}
