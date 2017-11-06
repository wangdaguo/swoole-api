<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\Commonweal;

class H5CommonwealController extends BaseController
{
    public function actionList()
    {/*{{{*/
        $this->setProfileSwitch();
        $shmCacheKey = 'shmCache_commonweal';
        $this->sendShmCache($shmCacheKey);
        $data = Commonweal::getList();
        $this->setTrack('getList->end');
        $this->send($data, $shmCacheKey, 3);

    }/*}}}*/

    public function actionInfo()
    {/*{{{*/
        $id = intval($this->get('id', 0));
        if($id < 0)
        {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode);
        }
        $shmCacheKey = 'shmCache_commonweal_'. $id;;
        $this->sendShmCache($shmCacheKey);
        $data = Commonweal::getInfo($id);
        $this->send($data, $shmCacheKey, 3);
    }/*}}}*/

}
