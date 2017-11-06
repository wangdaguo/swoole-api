<?php
namespace apps\controllers;

use apps\classes\ErrorConfig;
use apps\classes\PubFunction;
use apps\models\VoteQuestion;
use apps\models\ActivityCommon;
use apps\models\VoteParticipate;
use apps\models\User;

class H5VoteController extends BaseController
{
    public function actionInfo()
    {/*{{{*/
        $id = $this->post('id');
        if (!isset($id)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION);
        }
        $voteInfo = VoteQuestion::getVoteInfo($id);
        if (empty($voteInfo)) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA);
        }

        $response['voteInfo'] = $voteInfo['voteInfo'];
        $response['voteInfo']['currentTime'] = time();
        $response['optionList'] = $voteInfo['optionList'];

        if ($voteInfo['voteInfo']['images'] !== "") {
            $response['banner'] = json_decode($voteInfo['voteInfo']['images']);
        }

       $response['participateList'] = VoteParticipate::getList($id, VoteParticipate::PAGE_ONE, VoteParticipate::PAGE_SIZE);
        if($this->uid > 0)
        {
            $joinOptionIds = VoteParticipate::getJoinOptionIds($id, $this->uid);
            $response['optionIds'] = $joinOptionIds;
            $response['isParticipated'] = $joinOptionIds ? true : false;
        } else {
            $response['optionIds'] = [];
            $response['isParticipated'] = false;
        }
        $this->send($response);
    }/*}}}*/

    public function actionVote()
    {/*{{{*/
        $response = [
            'isSuccess' => 0,
        ];
        if ($this->uid <= 0) {
            $errorCode = ErrorConfig::SC_ACCOUTN_USER_TOKEN_NOTMATCH;
            $this->sendError($errorCode, '', $response);
        }
        $vid = $this->post('vid');
        $maxOptionNum = $this->post('maxOptionNum');
        $options = $this->post('options', []);

        if (!$vid || !$maxOptionNum || empty($options) || $maxOptionNum < count($options)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION, '', $response);
        }

        $activityCommon = ActivityCommon::getInfo($vid);
        if(empty($activityCommon))
        {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION, '', $response);
        }
        $time = time();
        if($time < $activityCommon['startTime'] 
            || $time > $activityCommon['endTime']
        )
        {
            $this->sendError(ErrorConfig::PHP_ACTIVITY_YET_END);
        }
        $userInfo = User::getUserInfoByUid($this->uid);
        if(empty($userInfo))
        {
            $errorCode = ErrorConfig::SC_SYSTEM_ERROR;
            $this->sendError($errorCode, '', $response);
        }

        //$this->checkIpLimit($vid);

        //check to see if current user are involved
        $joinOptionIds = VoteParticipate::getJoinOptionIds($vid, $this->uid);
        if ($joinOptionIds) {
            $errorCode = ErrorConfig::PHP_VOTE_IS_PARTICIPATED;
            $this->sendError($errorCode, '', $response);
        }

        
        $result = VoteQuestion::vote($vid, $this->uid, $maxOptionNum, $options, $userInfo);

        $response = [
            'isSuccess' => intVal($result),
        ];
        $this->send($response);
    }/*}}}*/

    public function actionParticipateList()
    {/*{{{*/
        $response = [];
        $vid = $this->post('vid');
        $page = $this->post('page', VoteParticipate::PAGE_ONE);
        $pageSize = $this->post('pageSize', VoteParticipate::PAGE_SIZE);
        $pageSize = 50;
        $lastId = $this->post('lastId', 0);
        $response['participateList'] = VoteParticipate::getList($vid, $page, $pageSize, $lastId);
        $activityCommon = ActivityCommon::getInfo($vid);
        $response['participateCount'] = $activityCommon['number'];
        $this->send($response);
    }/*}}}*/

    public function getOptionParticipatedCount($id)
    {/*{{{*/
        return Vote::getOptionParticipatedCount($id);
    }/*}}}*/

    private function checkIpLimit($vid)
    {/*{{{*/
        $response = [
            'isSuccess' => 0,
        ];
        $ip = PubFunction::getIp();
        if (VoteParticipate::checkIpLimit($vid, $ip)) {
            $this->sendError(ErrorConfig::PHP_VOTE_SYSTEM_IS_BUSY, '', $response);
        }
    }/*}}}*/
}
