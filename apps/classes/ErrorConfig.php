<?php
namespace apps\classes;
class ErrorConfig 
{
    //0x000000---0x000FFF 为正确码
    //0x001000---max为错误码
    //DEFAULT = 0x000000;//默认值  不能使用
    /*{{{*/
    const SC_SUCCESS =  "0x000001"; //接口成功返回数据
    const SC_THIRD_PARTY_ALREADY_BIND = "0x000100";//第三方已绑定
    const SC_THIRD_CHECK_USER_NOT_REGISTER = "0x000101"; //三方检查用户未注册

    const SC_PARAMETER_INVALID =  "0x001000"; // 客户端请求参数无效
    const SC_ACCOUTN_USER_TOKEN_NOTMATCH  = "0x002000"; // 用户token不匹配

    const SC_SMS_VERIFY_CODE_NOMOBILE = "0x001001";// 检查是否有效的大陆手机号码
    const SC_SMS_VERIFY_CODE_FAIL = "0x001002"; // 移动手机号码发送失败
    const SC_SMS_VERIFY_CODE_NOTMATCH = "0x001003"; //验证码不匹配
    const SC_ACCOUNT_MOBILE_PHONE_REPETITION  = "0x001004"; // 手机已经注册
    const SC_ACCOUNT_MOBILE_PHONE_ALLREADY_BIND  = "0x001005"; // 手机已经绑定其它手机号
    const SC_ACCOUTN_USER_NAME_FORBIDDEN = "0x001006"; //用户名被禁止
    const SC_ACCOUTN_THIRD_CHECK_FAIL = "0x001007"; //三方验证失败
    const SC_ACCOUNT_USER_NAME_OVER_LONG = "0x001008"; //用户名过长

    const SC_ACCOUNT_PASSWORD_ERROR = "0x001013"; //密码错误
    const SC_ACCOUNT_USER_NOT_EXIST = "0x001014"; //用户不存在
    const SC_ACCOUTN_USER_FORBIDDEN = "0x001017"; //用户被禁止登陆
    const SC_ACTIVITY_NOT_CONDITIONS = 0x006005; //不符合活动条件
    const SC_SYSTEM_ERROR = "0xffffff"; //系统异常

    const PHP_PARAMS_EXCEPTION  = "0x101002"; //参数异常
    const PHP_NOT_DATA = "0x101003"; //没有数据
    const PHP_TASK_DO_FAIL = "0x101004"; //做任务失败
    const PHP_ACTIVITY_NOT_BEGIN = "0x101005"; //活动没有开始
    const PHP_ACTIVITY_YET_END = "0x101006"; //活动已经结束

    //投票
    const PHP_VOTE_IS_PARTICIPATED = "0x101007"; //已经投票
    const PHP_VOTE_SYSTEM_IS_BUSY = "0x101008"; //已经投票

    /*}}}*/
    /*{{{*/
    protected static $gameErrorMessages = [
        self::SC_SUCCESS => "接口成功返回数据",
        self::SC_THIRD_PARTY_ALREADY_BIND => "第三方已绑定",
        self::SC_THIRD_CHECK_USER_NOT_REGISTER => "三方检查用户未注册",

        self::SC_PARAMETER_INVALID =>  "客户端请求参数无效",
        self::SC_ACCOUTN_USER_TOKEN_NOTMATCH  => "用户token不匹配",

        self::SC_SMS_VERIFY_CODE_NOMOBILE => "检查是否有效的大陆手机号码",
        self::SC_SMS_VERIFY_CODE_FAIL => "移动手机号码发送失败",
        self::SC_SMS_VERIFY_CODE_NOTMATCH => "验证码不匹配",
        self::SC_ACCOUNT_MOBILE_PHONE_REPETITION  => "手机已经注册",
        self::SC_ACCOUNT_MOBILE_PHONE_ALLREADY_BIND  => "手机已经绑定其它手机号",
        self::SC_ACCOUTN_USER_NAME_FORBIDDEN => "用户名被禁止",
        self::SC_ACCOUTN_THIRD_CHECK_FAIL => "三方验证失败",
        self::SC_ACCOUNT_USER_NAME_OVER_LONG => "用户名过长",

        self::SC_ACCOUNT_PASSWORD_ERROR => "密码错误",
        self::SC_ACCOUNT_USER_NOT_EXIST => "用户不存在",
        self::SC_ACCOUTN_USER_FORBIDDEN => "用户被禁止登陆",
        self::SC_ACTIVITY_NOT_CONDITIONS => "不符合活动条件",
        self::SC_SYSTEM_ERROR => "系统异常",

        self::PHP_PARAMS_EXCEPTION => "参数异常",
        self::PHP_NOT_DATA => "没有数据",
        //2016-11-16 11:33
        self::PHP_TASK_DO_FAIL => "做任务失败",
        self::PHP_ACTIVITY_NOT_BEGIN => "活动还没开始",
        self::PHP_ACTIVITY_YET_END => "活动已经结束",

        self::PHP_VOTE_IS_PARTICIPATED => "已经参与投票",
        self::PHP_VOTE_SYSTEM_IS_BUSY => "投票系统繁忙",
    ];
    /*}}}*/
    public static function getErrorMessage($errorCode)
    {/*{{{*/
        if(isset(self::$gameErrorMessages[$errorCode]))
        {
            return self::$gameErrorMessages[$errorCode];
        }
        return '';
    }/*}}}*/

}
