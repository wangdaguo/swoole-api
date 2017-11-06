<?php
namespace apps\models;
use Swoole;

class User extends Swoole\Model
{
    public static function getUserInfoByUserToken($userToken)
    {
        $userObj = new \apps\classes\thrift\UserTClient();
        $userInfo = $userObj->getUserDetailByUserToken($userToken);
        return get_object_vars($userInfo);
    }
    public static function getUserInfoByUid($uid)
    {
        $userObj = new \apps\classes\thrift\UserTClient();
        $userInfo = $userObj->getUserInfo(0, $uid);
        return is_object($userInfo) ? get_object_vars($userInfo) : [];
    }


}
