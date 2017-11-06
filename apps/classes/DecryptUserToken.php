<?php
namespace apps\classes;

class DecryptUserToken
{
    const ASCII_ZERO = 48; //0
    const ASCII_NINE = 57; //9
    const ASCII_A = 65; //A
    const ASCII_Z = 90; //Z
    const ASCII_SMALL_A = 97; //a
    const ASCII_SMALL_Z = 122; //z

    const CHECK_TOKEN_LEN = 14;

    const UID_MAX = 100000000;
    const UID_MIN = 10000001;

    protected  $_check = true;
    private $data = 0;
    private $current = 1;

    public function getUidFromUserToken($userToken)
    {/*{{{*/
        $len = strlen($userToken);
        if (self::CHECK_TOKEN_LEN >= $len) {
            return 0;
        }
        return $this->tokenStringToUid(substr($userToken, 14));
    }/*}}}*/

    public  function tokenStringToUid($str)
    {/*{{{*/
        $arr = str_split($str, 1);
        $newArr = array_reverse($arr);
        $result  = array_map([$this, "getJZint"], $newArr);
        if ($this->_check == false) {
            return 0;
        }
        $pop = array_pop($result);
        //uid 位数判断
        if ($pop > self::UID_MAX || $pop < self::UID_MIN) {
            return 0;
        }
        return $pop;
    }/*}}}*/

    public function getJZint($char)
    {/*{{{*/
        $value = ord($char);

        if ($value >= self::ASCII_ZERO && $value <= self::ASCII_NINE) {
            $value = $value - self::ASCII_ZERO;
        } else if ($value >= self::ASCII_A && $value <= self::ASCII_Z) {
            $value = $value - self::ASCII_A + 10;
        } else if ($value >= self::ASCII_SMALL_A && $value <= self::ASCII_SMALL_Z) {
            $value = $value - self::ASCII_SMALL_A + 36;
        } else {
            $this->_check = false;
            return 0;
        }
        $this->data += $value * $this->current;
        $this->current *= 62;
        return $this->data;
    }/*}}}*/

    public  function getJZintBak($char)
    {/*{{{*/
        if ($char >= '0' && $char <= '9') {
            return ord($char) - ord('0');
        } else if ($char >= 'A' && $char <= 'Z') {
            return ord($char) - ord('A') + 10;
        } else if ($char >= 'a' && $char <= 'z') {
            return ord($char) - ord('a') + 36;
        } else {
            return 0;
        }
    }/*}}}*/
}
