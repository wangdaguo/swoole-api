<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\LiveInterview;
use apps\models\LiveInterviewSummary;

class H5LiveInterviewController extends BaseController
{

    public function actionRecommend()
    {/*{{{*/

        $this->setProfileSwitch();
        $shmCacheKey = 'shmCache_recommen_live_interview';
        $this->sendShmCache($shmCacheKey);
        $data = LiveInterview::getRecommendList();
        $this->setTrack('getList->end');
        $this->send($data, $shmCacheKey, 5);
    }/*}}}*/

    public function actionList()
    {/*{{{*/
        $this->setProfileSwitch();
        $page = $this->post('page', 1);
        $pageSize = $this->post('pageSize', 10);
        $shmCacheKey = '';
        if($page == 1)
        {
            $shmCacheKey = 'shmCache_live_interview';
            $this->sendShmCache($shmCacheKey);
        }
        $data = LiveInterview::getList($page, $pageSize);
        $this->setTrack('getList->end');
        $this->send($data, $shmCacheKey, 10);

    }/*}}}*/

    public function actionDescription()
    {/*{{{*/
        $description = LiveInterviewSummary::getDesciption();
        $this->send($description);
    }/*}}}*/

}
