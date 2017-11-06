<?php
namespace apps\controllers;
use Swoole;
use Swoole\Exception\Business;
use apps\classes\DecryptUserToken;
use apps\classes\ErrorConfig;
use apps\classes\LibShmCache;
use apps\classes\PubFunction;

class BaseController extends Swoole\Controller
{
    protected $uid = 0;
    protected $userToken = '';

    public function __construct(\Swoole $swoole)
    {
        $this->getUid();
        parent::__construct($swoole);
    }
    protected function getUid()
    {
        $userToken = '';
        $uid = 0;
        if (isset($_SERVER['HTTP_USERTOKEN'])) {
            $userToken = $_SERVER['HTTP_USERTOKEN'];
        } else if (false == empty($_REQUEST['userToken'])) {
            $userToken = $_REQUEST['userToken'];
        }
        if ($userToken) {
            $this->userToken = $userToken;
            $uid  = (new DecryptUserToken())->getUidFromUserToken($userToken);
        }
        $this->uid = $uid;
        return $uid;
    }


    function send($data, $shmCacheKey = '', $shmCacheTime = 1)
    {
        $json = array('errcode' => 0, 'errmsg' => '', 'data' => $data);
        $this->http->header('Content-type', 'application/json');
        $jsonData =  json_encode($json);
        if($shmCacheKey && $data)
        {
            LibShmCache::set($shmCacheKey, $jsonData, $shmCacheTime);
        }
        throw (new Business($jsonData));
    }

    function sendError($errorCode, $message = '', $data = [])
    {
        $errorMessage = $message ? $message : ErrorConfig::getErrorMessage($errorCode);
        $json = array('errcode' => $errorCode, 'errmsg' => $errorMessage, 'data' => $data);
        $this->http->header('Content-type', 'application/json');
        $jsonData =  json_encode($json);
        throw (new Business($jsonData));
    }

    function sendShmCache($shmCacheKey)
    {
        $shmCacheValue =LibShmCache::get($shmCacheKey);
        if($shmCacheValue)
        {
            $this->http->header('Content-type', 'application/json');
            throw (new Business($shmCacheValue));
        }
    }

    function setTrack($track)
    {
        $this->swoole->profile->setTrack('c->' . $track);
    }

    function setProfileSwitch($switch = true)
    {
        $this->swoole->profile->setSwitch($switch);
    }
    public function getFantuanUserAgent()
    {/*{{{*/
        return isset($_SERVER['HTTP_USERAGENT'])?$_SERVER['HTTP_USERAGENT']:null;
    }/*}}}*/

    public function getFantuanUserAgentArr()
    {/*{{{*/
        $userAgent = $this->getFantuanUserAgent();
        if (!$userAgent) {
            return [];
        }
        $dataArr = explode(' ', $userAgent);
        $arr = [];
        if (!$dataArr) {
            return [];
        }
        foreach ($dataArr as $v) {
            if (!trim($v)) {
                continue;
            }
            $itemArr = explode('/', $v);
            if (count($itemArr) == 2) {
                if (strtolower($itemArr[0]) == 'android' || strtolower($itemArr[0]) == 'ios') {
                    $arr['os'] = strtolower($itemArr[0]);
                    $arr['channel'] = strtolower($itemArr[1]);
                }
                $arr[$itemArr[0]] = $itemArr[1];
            }
        }
        $arr['version'] = isset($arr['version']) ? $arr['version'] : '';
        $arr['os'] = isset($arr['os']) ? $arr['os'] : '';
        if (!isset($arr['os'])) {
            $arr['os'] = isset($_GET['os']) ? $_GET['os'] : '';
        }
        $arr['osType'] = ($arr['os'] == 'ios') ? 2 : 1;
        return $arr;
    }/*}}}*/
}
