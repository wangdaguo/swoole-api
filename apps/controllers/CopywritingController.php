<?php
namespace apps\controllers;
use Swoole;
use apps\controllers\BaseController;
use apps\classes\LibShmCache;
use apps\models\Copywriting;
use apps\classes\ErrorConfig;

class CopywritingController extends BaseController
{
    public function actionIndex()
    {/*{{{*/
        $data = [
            'title' => '',
            'images' => '',
            'content' => '',
            'redirectUrl' => '',
        ];
        $feature = $this->post('feature');

        $result = $this->getFantuanUserAgentArr();
        if (empty($feature)) {
            $errorCode = ErrorConfig::PHP_PARAMS_EXCEPTION;
            $this->sendError($errorCode);
        }
        $osType = isset($result['osType']) ? $result['osType'] : -1;
        switch ($osType) {
            case 1:
                $platform = 'android';
                break;
            case 2:
                $platform = 'ios';
                break;
            default:
                $platform = 'pc';
        }
        $info = Copywriting::getData($platform, $feature);
        if (isset($info['empty']) && 1 == sizeof($info)) {
            $info = [
                'title' => '',
                'images' => '',
                'content' => '',
                'redirectUrl' => '',
            ];
        }
        $info['images'] = $info['images'] ? explode(';', $info['images']) : [];
        if (Copywriting::isInteract($feature)) {
            $info['content'] = Copywriting::getRandomContent($info['content']);
        }
        $data = [
            'title' => $info['title'],
            'images' => $info['images'],
            'content' => $info['content'],
            'redirectUrl' => $info['redirectUrl'],
        ];
        $this->send($data);
    }/*}}}*/
}
