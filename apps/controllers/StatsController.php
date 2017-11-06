<?php
namespace apps\controllers;
use Swoole;
use apps\classes\ErrorConfig;
use apps\classes\LibShmCache;
class StatsController extends BaseController
{
    public function actionShm()
    {
        $statsData['host'] = gethostname();
        $statsData['data'] = LibShmCache::stats();
        $this->send($statsData);
    } 
}
