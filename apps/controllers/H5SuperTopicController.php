<?php
namespace apps\controllers;

use apps\classes\ErrorConfig;
use apps\classes\PubFunction;
use apps\models\SuperTopic;
use apps\models\TopicalInformation;

class H5SuperTopicController extends BaseController
{
    public function actionIndex()
    {/*{{{*/
        $id = $this->post('id');
        if (!isset($id)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION);
        }

        $response = SuperTopic::getSuperTopicInfoFromMemcache($id);
        if (!$response) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA);
        }

        $this->send($response);
    }/*}}}*/

    public function actionTopicalDetail()
    {/*{{{*/
        $id = $this->post('id');
        if (!isset($id)) {
            $this->sendError(ErrorConfig::PHP_PARAMS_EXCEPTION);
        }

        $response = TopicalInformation::getTopicalInformationFromMemcache($id);
        if (!$response) {
            $this->sendError(ErrorConfig::PHP_NOT_DATA);
        }

        $this->send($response);
    }/*}}}*/
}
