<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\classes\ErrorConfig;
use apps\models\HotTopicShare;

class HotTopicShareController extends BaseController
{

    public function actionIndex()
    {/*{{{*/

        $type = $this->post('type', 0);
        $data = HotTopicShare::getList();
        $this->send($data);
    }/*}}}*/

}
