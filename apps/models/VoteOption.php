<?php
namespace apps\models;

class VoteOption extends BaseModel
{
    public static function tableName()
    {/*{{{*/
        return 'vote_option';
    }/*}}}*/

    public static function getList($id, $useCache = true)
    {/*{{{*/
        $cacheKey = 'vote_option_' . $id;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $formatList = $mcClient->get($cacheKey);
            if($formatList)
            {
                return $formatList;
            }
        }
        $formatList = [];
        $sql = 'select id, questionId, optionTitle,optionContent,img,count from ' . self::tableName() . ' where questionId = ' . intval($id);
        $list = self::getSlaveClusterDb()->queryAll($sql);
        if($list)
        {
            $formatList = self::getFormatList($list);
            $mcClient->set($cacheKey, $formatList, 300);
        }
        return $formatList;
    }/*}}}*/

    public static function getFormatList($optionInfoArr)
    {/*{{{*/
        $data = [];
        $sum = 0;
        foreach ($optionInfoArr as $optionInfo) {
            $data['list'][] = [
                'id' => $optionInfo['id'],
                'vid' => $optionInfo['questionId'],
                'optionCount' => $optionInfo['count'],
                'optionContent' => $optionInfo['optionContent'],
                'img' => $optionInfo['img'],
            ];
            $sum += $optionInfo['count'];
        }
        $data['sum'] = $sum;
        return $data;

    }/*}}}*/
}
