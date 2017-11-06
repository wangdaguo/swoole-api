<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\models\BannerManage;
use apps\classes\ErrorConfig;


class BannerManageController extends BaseController
{
    public function actionList()
    {/*{{{*/
        $response = [
            'list' => [
            ],
        ];
        $sid = $this->post('sid');
        $type = $this->post('type', 1);
        $result = $this->getFantuanUserAgentArr();
        if (!isset($result['osType'])) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode);
        }
        $platform = $result['osType'];
        if ((int)$sid <= 0 || !$platform) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode);
        }

        $tasksList = BannerManage::getBannerList($sid, $type, $platform);
        $response['list'] = $tasksList;
        $this->send($response);
    }/*}}}*/
}
