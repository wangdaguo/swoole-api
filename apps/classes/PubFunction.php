<?php
namespace apps\classes;

class PubFunction
{
    /*
     * java  hashcode 
     */
    public static function hashCode($s)
    {/*{{{*/
        $len = strlen($s);
        $hash = 0;
        for ($i=0; $i<$len; $i++) {
            //一定要转成整型
            $hash = (int)($hash*31 + ord($s[$i]));
            //64bit下判断符号位
            if (($hash & 0x80000000) == 0) {
                //正数取前31位即可
                $hash &= 0x7fffffff;
            } else {
                //负数取前31位后要根据最小负数值转换下
                $hash = ($hash & 0x7fffffff) - 2147483648;
            }
        }
        return $hash;
    }/*}}}*/

    public static function getJavaTableById($id, $size = 100)
    {/*{{{*/
        $code = self::hashCode((string)$id);
        $size = $size > 0 ? $size : 1;
        return abs($code % $size);
    }/*}}}*/

    //根据md5 取最后一位
    public static function getTableId($key)
    {/*{{{*/
        $num = ord(substr(md5($key),0,1));
        if($num >=97){
            $id = $num-97 + 10;
        }
        else{
            $id = $num-48;
        }
        return $id;
    }/*}}}*/

    /**
     *
     * 在网址后面增加新的Query参数
     * 注：只做了简单处理，需要优化
     */
    public static function addParametersToUrl($url, Array $parameters)
    {/*{{{*/
        $data = [];
        foreach($parameters as $key=>$parameter)
        {
            if($parameter !== "")
            {
                $data[$key] = $parameter;
            }
        }
        $queryStr = http_build_query($data);
        $pathInfo = parse_url($url);

        if(empty($pathInfo['query']))
        {
            $url .= '?';
        } else {
            $url .= '&';
        }

        return $url.$queryStr;
    }/*}}}*/

    //二维数组排序
    public static function tdSort(&$arr, $fieldA, $sortA = SORT_ASC, $fieldB = '', $sortB = SORT_ASC, $fieldC = '', $sortC = SORT_ASC)
    {/*{{{*/
        if (!is_array($arr) || count($arr) < 1) {
            return false;
        }
        foreach ($arr as $rs) {
            foreach ($rs as $key => $value) {
                $arrTmp["{$key}"][] = $value;
            }
        }
        if (empty($fieldB)) {
            array_multisort($arrTmp[$fieldA], $sortA, $arr);
        } elseif (empty($fieldC)) {
            array_multisort($arrTmp[$fieldA], $sortA, $arrTmp[$fieldB], $sortB, $arr);
        } else {
            array_multisort($arrTmp[$fieldA], $sortA, $arrTmp[$fieldB], $sortB, $arrTmp[$fieldC], $sortC, $arr);
        }
        return true;
    }/*}}}*/
    public static function getRemainSeconds()
    {/*{{{*/
        return 86400 - (time() - strtotime(date('Y-m-d')));
    }/*}}}*/

    public static function getWeek($date)
    {/*{{{*/
        $lastday=date('Y-m-d',strtotime("$date Sunday")); 
        $firstday=date('Y-m-d',strtotime("$lastday -6 days")); 
        return array($firstday,$lastday); 
    }/*}}}*/

    public static function getMonth($date)
    {/*{{{*/
        $firstday = date('Y-m-01',strtotime($date)); 
        $lastday = date('Y-m-d',strtotime("$firstday +1 month -1 day")); 
        return array($firstday,$lastday); 
    }/*}}}*/
    public static function getIp()
    {/*{{{*/
        $realIp = '';
        if (isset($_SERVER))
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                //取X-Forwarded-for中第一个非unknown的有效Ip字符串
                foreach ($arr as $ip)
                {
                    $ip = trim($ip);
                    if ($ip != 'unknown')
                    {
                        $realIp = $ip;
                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realIp = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR']))
                {
                    $realIp = $_SERVER['REMOTE_ADDR'];
                } else {
                    $realIp = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR'))
            {
                $realIp = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realIp = getenv('HTTP_CLIENT_IP');
            } else {
                $realIp = getenv('REMOTE_ADDR');
            }
        }

        $onlineIp = null;
        preg_match("/[\d\.]{7,15}/", $realIp, $onlineIp);
        return !empty($onlineIp[0]) ? $onlineIp[0] : '0.0.0.0';
    }/*}}}*/

}
