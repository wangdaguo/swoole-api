<?php
namespace apps\models;

use apps\classes\PubFunction;


class LiveInterviewSummary extends BaseModel
{

    public static function getInfo($useCache = true)
    {/*{{{*/
        $cacheKey = 'live_interview_summary_base';
        $mcClient = self::getCacheCluster2Client();
        $formatData = [];
        if($useCache)
        {
            $formatData= $mcClient->get($cacheKey);
            if($formatData)
            {
                return $formatData;
            }
        }
        $formatData = self::getFormatData();
        $formatData && $mcClient->set($cacheKey, $formatData, 300);
        return $formatData;
        
    }/*}}}*/

    public static function getDesciption($useCache = true)
    {/*{{{*/
        $cacheKey = 'live_interview_summary_desc';
        $mcClient = self::getCacheCluster2Client();
        $description = '';
        if($useCache)
        {
            $description= $mcClient->get($cacheKey);
            if($description)
            {
                return $description;
            }
        }
        $data = self::getData();
        if($data)
        {
            $description = $data['description'];
        }

        $description && $mcClient->set($cacheKey, $description, 300);
        return $description;

    }/*}}}*/

    public static function getFormatData()
    {
        $data = self::getData();
        $formatData = [];
        if($data)
        {
            $formatData = [
                'bill' => $data['bill'],
                'images' => json_decode($data['images'], 1)
            ];
        }
        return $formatData;
    }

    public static function clearCache()
    {/*{{{*/
        self::getInfo(false);
        self::getDesciption(false);
        return true;
    }/*}}}*/

    public static function getData()
    {/*{{{*/
        $sql = 'select * from ' . self::tableName();
        return self::getStarBaseDb()->queryOne($sql);
    }/*}}}*/

    public static function tableName()
    {/*{{{*/
        return 'live_interview_summary';
    }/*}}}*/
}
