<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\models\LiveInterview;
use apps\models\Commonweal;
use apps\models\LiveInterviewSummary;
use apps\models\AdCard;
use apps\models\Dict;
use apps\models\ActivityCommon;
use apps\models\VoteOption;
use apps\models\VoteParticipate;
use apps\models\VoteQuestion;
use apps\models\HotTopicShare;
use apps\models\Copywriting;
use apps\models\BannerManage;
use apps\models\SuperTopic;
use apps\models\TopicalInformation;
use apps\models\ActivityParticipateLottery;
use apps\models\ActivityInfo;


class SiteController extends BaseController
{

    public function actionClearCache()
    {/*{{{*/
        $func = $this->get('func');
        $params = $_GET;
        $functionName = "clear". ucfirst($func);;
        if(method_exists($this, $functionName))
        {
            $result = $this->$functionName($params);
            $message = $result ? '操作成功' : '操作失败';
        } else {
            $message = '方法不存在';
        }
        $this->send($message);

    }/*}}}*/
    public function clearCommonweal($id)
    {
        Commonweal::clearCache($id);
        return true;
    }

    public function clearAdCard($params)
    {
        $position = $params['position'];
        $sid= $params['sid'];
        AdCard::clearCache($position, $sid);
        return true;
    }

    public function clearLiveInterviewSummary($params)
    {
        LiveInterviewSummary::clearCache($params['id']);
        return true;
    }

    public function clearLiveInterview($params)
    {
        LiveInterview::clearCache();
        return true;
    }
    public function clearDict($params)
    {
        Dict::clearCache($params['key']);
        return true;
    }
    public function clearActivity($params)
    {
        $vid = $params['id'];
        ActivityCommon::getInfo($vid, false);
        return true;
    }
    public function clearVoteQuestion($params)
    {
        $vid = $params['id'];
        VoteOption::getList($vid, false);
        VoteQuestion::getInfo($vid, false);
        return true;
    }
    public function clearActivityInfo($params)
    {
        $vid = $params['id'];
        ActivityInfo::getInfo($vid, false);
        ActivityParticipateLottery::getLotteryListFromCache($vid, 1,50, false);
        return true;
    }


    public function clearHotTopicShareConfig($params)
    {
        $type = $params['type'];
        HotTopicShare::clearCache();
        return true;
    }
    public function clearCopywriting($params)
    {
        $platform = $params['platform'];
        $feature = $params['feature'];
        Copywriting::clearCache($platform, $feature);
        return true;
    }
    public function clearBannerManage($params)
    {
        $sid = $params['sid'];
        $type = $params['type'];
        $platform = $params['platform'];
        BannerManage::clearCache($sid, $type, $platform);
        return true;
    }
    public function clearSuperTopic($params)
    {
        $id = $params['id'];
        SuperTopic::getSuperTopicInfoFromMemcache($id, false);
        return true;
    }
    public function clearTopicalInformation($params)
    {
        $id = $params['id'];
        TopicalInformation::getTopicalInformationFromMemcache($id, false); 
        return true;
    }









}
