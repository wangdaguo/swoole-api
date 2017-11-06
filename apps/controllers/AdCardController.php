<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\AdCard;
use apps\models\Dict;

class AdCardController extends BaseController
{

    public function actionIndex()
    {/*{{{*/
        $position = $this->post('position','');
        $sid = $this->post('sid', 0);
        if(!$position)
        {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode, '', $data);
        }
        $shmCacheKey = 'ad_card_' . $position . '_' . $sid;
        $this->sendShmCache($shmCacheKey);

        $time = time();
        $filterArr = [];
        $list = AdCard::getList($position, $sid);
        foreach($list as $key => $item)
        {

            if($time >= strtotime($item['startTime']) 
                && $time <= strtotime($item['endTime']))
            {
                $filterArr[] = $item;
            }
        }
        $data['list'] = $filterArr;
        $data['interval'] = intval(Dict::getInfoByKey(Dict::AD_CARD));
        $this->send($data, $shmCacheKey, 5);
    }/*}}}*/

}
