<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\models\Tasks;
use apps\models\Timeline;
use apps\models\Star;
use apps\classes\ErrorConfig;

class TasksController extends BaseController
{
    const WEIBO_URL = "http://m.weibo.cn/%s/%s";

    public function actionList()
    {/*{{{*/
        // 记录性能消耗
        $this->setProfileSwitch();
        $sid = $this->post('sid');
        $type = $this->post('type', -1);
        $response = [
            'star' => [
                'name' => '',
                'avatar' => '',
            ],
            'tasks' => [],
            'position' => -1,
            'paidShine' => 0,
        ];

        if ((int)$sid <= 0) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION);
        }
        $tasksList = Tasks::getTaskList($sid, $type);
        $this->setTrack('Tasks::getTaskList');
        $starData = Star::getStarBySidFromMc($sid);
        $this->setTrack('Star::getStarBySidFromMc');
        if (empty($starData)) {
            $errorCode = ErrorConfig::PHP_NOT_DATA;
            $this->sendError($errorCode, '', $response);
        } else {
            $response['star'] = [
                'name' => $starData['name'],
                'avatar' => $starData['avatar'],
            ];
        }
        $tasksList = $this->formatTasks($this->uid, $sid, $tasksList, $starData);
        $this->setTrack('formatTasks');
        $response['tasks'] = $tasksList;
        $response['position'] = Star::getStarShinePos($sid);
        $this->setTrack('Star::getStarShinePos');
        $response['paidShine'] = Star::getStarDetail($this->uid, $sid);
        $this->setTrack('Star::getStarDetail');
        $response['time'] = time();
        $this->send($response);
    }/*}}}*/

    public function formatTasks($uid, $sid, $tasksList, $starInfo)
    {/*{{{*/
        foreach ($tasksList as $key => &$task) {
            $task['content'] = str_replace('{{star_name}}', $starInfo['name'], $task['content']);
            $task['url'] = str_replace('{{star_name}}', $starInfo['name'], $task['url']);
            if ($task['type'] == Tasks::TYPE1) {/*{{{*/
                if (is_numeric($task['content'])) {
                    if (is_numeric($task['url'])) {
                        $wuid = $task['url'];
                        $originStarInfo = Star::getStarByWuidFromRedis($wuid);
                        //const WEIBO_URL = "http://m.weibo.cn/%s/%s";
                        $task['url'] = sprintf(self::WEIBO_URL, $wuid, $task['content']);
                        $timeLine = $this->getTimeLine($originStarInfo['id'], Star::TWEET, $task['url']);
                        $task['content'] = !empty($timeLine['content']) ? $timeLine['content'] : '';
                    } else {
                        $task['url'] = sprintf(self::WEIBO_URL, $starInfo['wuid'], $task['content']);
                        $timeLine = $this->getTimeLine($sid, Star::TWEET, $task['url']);
                        $task['content'] = !empty($timeLine['content']) ? $timeLine['content'] : '';
                    }
                } else {
                    $timeLine = $this->getTimeLine($sid, Star::TWEET);
                    $task['content'] = !empty($timeLine['content']) ? $timeLine['content'] : '';
                    $task['url'] = !empty($timeLine['url']) ? $timeLine['url'] : '';
                }
            }/*}}}*/
            $task['status'] = (int)Tasks::checkTask($uid, $sid, $task['id']);
        }
        return $tasksList;
    }/*}}}*/

    public function getTimeLine($sid, $action, $url = '')
    {/*{{{*/
        $timeLine = [];
        if ($url) {
            $timeLine = Timeline::getTimelineDataBySidAndUrl($sid, $url);
        } else {
            $timeLine = Timeline::getTimelineDataBySidAndActionFromRedis($sid, $action);
        }
        return $timeLine;
    }/*}}}*/

    public function actionDo()
    {/*{{{*/
        $this->setProfileSwitch();
        $response = [
            'sid' => 0,
            'status' => 0,
            'paidShine' => 0,
            'position' => 0,
        ];
        $sid = intval($this->post('sid'));
        $tid = intval($this->post('taskId'));

        if ((int)$sid <= 0 || (int)$tid <= 0) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode,'', $response);
        }
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $response);
        }

        $result = Tasks::doTask($this->uid, $this->userToken, $sid, $tid);
        $this->setTrack('tasks::doTask');
        if (!$result) {
            $errorCode = ErrorConfig::PHP_TASK_DO_FAIL;
            $this->sendError($errorCode, ErrorConfig::getErrorMessage($errorCode), $response);
        }
        $response['sid'] = $sid;
        $response['status'] = Tasks::DONE;
        $response['paidShine'] = Star::getStarDetail($this->uid, $sid);
        $this->setTrack('Star::getStarDetail');
        $response['position'] = Star::getStarShinePos($sid);
        $this->setTrack('Star::getStarShinePos');
        $this->send($response);
    }/*}}}*/
}
