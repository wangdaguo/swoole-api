<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\ActivityCommon;
use apps\models\ActivityInfo;
use apps\models\BaseModel;
use apps\models\ActivityParticipateLottery;
use apps\models\ActivityParticipate;
use apps\models\ActivityPartner;
use apps\models\User;

class H5ActivityController extends BaseController
{
    public function actionInfo()
    {/*{{{*/
        $activityId = $this->post('activityId');
        if (empty($activityId)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION);
        }
        $activityCommonInfo = ActivityCommon::getInfo($activityId);
        if (empty($activityCommonInfo)) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA);
        }

        $activityInfo = ActivityInfo::getInfo($activityId);
        if (empty($activityInfo)) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA);
        }
        $response = [];
        if ($activityInfo['images']) {
            $response['banner'] = json_decode($activityInfo['images'], true);
        } else {
            $response['banner'] = [];
        }
        $response['actname'] = $activityCommonInfo['title'];
        $response['linkTitle'] = empty($activityInfo['link_title']) ? '粉丝福利' : $activityInfo['link_title'];
        $response['desc'] = $activityInfo['content'];
        $response['starttime'] = date('Y-m-d', $activityCommonInfo['startTime']);
        $response['publisher'] = $activityCommonInfo['publisher'];
        $response['buttonText'] = $activityInfo['button_name'];
        if ($activityInfo['lotteryShow'] == 1) {
            $response['awardList'] = ActivityParticipateLottery::getLotteryList($activityId, 1, 50);
        } else {
            $response['awardList'] = [];
        }
        $response['isJoined'] = ($this->uid < 1) ? 0 : ActivityParticipate::checkParticipated($activityId, $this->uid);
        $leftTime = $activityCommonInfo['endTime'] - time();
        $leftTime  = $leftTime > 0 ? $leftTime : 0;
        $response['cutter'] = $leftTime;
        $response['partners'] = ActivityPartner::getListByActivityId($activityId);
        $fansArr = ActivityParticipate::getList($activityId, 1, 50);
        $response['fans'] = $fansArr;
        $this->send($response);
    }/*}}}*/
    public function actionPlay()
    {/*{{{*/
        $response = [
            'isSuccess' => 0,
        ];
        if ($this->uid < 1) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $response);
        }
        $activityId = $this->post('activityId');

        if (empty($activityId)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION, '', $response);
        }

        $activityCommonInfo = ActivityCommon::getInfo($activityId);
        if (empty($activityCommonInfo)) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA, '', $response);
        }
        $this->check($activityCommonInfo);


        $userInfo = User::getUserInfoByUid($this->uid);
        if(empty($userInfo))
        {
            $errorCode = ErrorConfig::SC_SYSTEM_ERROR;
            $this->sendError($errorCode, '', $response);
        }


        //$this->checkJoinNum($activityId);
        $result = ActivityParticipate::participate($activityId, $this->uid,$userInfo);
        $response = ['isSuccess' => (int)$result];
        $this->send($response);
    }/*}}}*/

    // 检查活动是否合法
    private function check($activityCommonInfo)
    {/*{{{*/
        $now = time();
        if ($now < $activityCommonInfo['startTime']) {
            $this->sendError(ErrorConfig::PHP_ACTIVITY_NOT_BEGIN);
        }

        if ($now > $activityCommonInfo['endTime']) {
            $this->sendError(ErrorConfig::PHP_ACTIVITY_YET_END);
        }
        return true;
    }/*}}}*/

    // 检查每个ip的参与人数是否超限
    private function checkJoinNum($activityId)
    {/*{{{*/
        $expire = 60;
        $ip = $this->getIp();
        $cacheKey = $ip . '_' . $activityId;
        $redis = BaseModel::getClusterRedisClient();
        $joinNum = $redis->incr($cacheKey);
        if ($joinNum == 1) {
            $redis->expire($cacheKey, $expire);
        }
        if ($joinNum > self::JOIN_NUM) {
            $this->send($response);
        }
    }/*}}}*/

    public function actionJoinList()
    {/*{{{*/
        $response = [
            'list' => [],
            'count' => 0,
        ];
        $activityId = intval($this->post('activityId'));
        $activityCommonInfo = ActivityCommon::getInfo($activityId);
        if (empty($activityCommonInfo)) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA, '', $response);
        }

        $page = intval($this->post('page', 1));
        $list = ActivityParticipate::getList($activityId, $page, 50);
        $response['list'] = $list;
        $response['count'] = $activityCommonInfo['number'];
        $this->send($response);
    }/*}}}*/

    public function actionAwardList()
    {/*{{{*/
        $response = [
            'list' => [],
        ];
        $activityId = intval($this->post('activityId'));
        if (empty($activityId)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION, '', $response);
        }
        $page = intval($this->post('page', 1));
        $pageSize = 50;
        $response['list'] = ActivityParticipateLottery::getLotteryList($activityId, $page, $pageSize);
        $this->send($response);
    }/*}}}*/

}
