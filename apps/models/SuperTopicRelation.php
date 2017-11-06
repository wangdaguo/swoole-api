<?php
namespace apps\models;

class SuperTopicRelation extends BaseModel
{
    const STAR = 0;
    const HOT_ACTIVITY = 1;
    const LIVE_INTERVIEW = 2;
    const FEATURED_TOPIC = 3;
    const TRAVEL_SCENE = 4;
    const TOPIC_HEATING = 5;
    const TOPICAL_INFORMATION = 6;

    public static function tableName()
    {/*{{{*/
        return 'super_topic_relation';
    }/*}}}*/

    public static function getSuperTopicRelation($newSuperTopicInfo, $id)
    {/*{{{*/
        $sql = 'select * from '.self::tableName().' where superTopicId = '.intval($id);
        $relationDataList = self::getSlaveDb()->queryAll($sql);

        $newSuperTopicInfo['starList'] = [];
        $newSuperTopicInfo['hotActivityList']['title'] = '热门活动';
        $newSuperTopicInfo['hotActivityList']['hotList'] = [];
        $newSuperTopicInfo['liveInterViewList']['title'] = '直播专访';
        $newSuperTopicInfo['liveInterViewList']['liveList'] = [];
        $newSuperTopicInfo['featuredTopicList']['title'] = '精选话题';
        $newSuperTopicInfo['featuredTopicList']['topicList'] = [];
        $newSuperTopicInfo['travelSceneList']['title'] = '行程现场';
        $newSuperTopicInfo['travelSceneList']['travel'] = [];
        $newSuperTopicInfo['topicHeatingList']['title'] = '话题加热';
        $newSuperTopicInfo['topicHeatingList']['topicHeating'] = [];
        $newSuperTopicInfo['topicalInformationList']['title'] = '公益资讯';
        $newSuperTopicInfo['topicalInformationList']['topicalInformation'] = [];
        foreach ($relationDataList as $relation)
        {
            switch (intval($relation['moduleType']))
            {
                case self::STAR:
                    $newSuperTopicInfo = self::getStarList($newSuperTopicInfo, $relation);
                    break;
                case self::HOT_ACTIVITY:
                    $newSuperTopicInfo = self::getHotList($newSuperTopicInfo, $relation);
                    break;
                case self::LIVE_INTERVIEW:
                    $newSuperTopicInfo = self::getLiveList($newSuperTopicInfo, $relation);
                    break;
                case self::FEATURED_TOPIC:
                    $newSuperTopicInfo = self::getTopicList($newSuperTopicInfo, $relation);
                    break;
                case self::TRAVEL_SCENE:
                    $newSuperTopicInfo = self::getTravelList($newSuperTopicInfo, $relation);
                    break;
                case self::TOPIC_HEATING:
                    $newSuperTopicInfo = self::getTopicHeatingList($newSuperTopicInfo, $relation);
                    break;
                case self::TOPICAL_INFORMATION:
                    $newSuperTopicInfo = self::getTopicalInformationList($newSuperTopicInfo, $relation);
                    break;
            }
        }
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getStarList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $starList = json_decode($relation['data'], true);
        $starList = is_null($starList) ? [] : $starList;
        $starSort = [];
        foreach ($starList as $key => $star)
        {
            $starSort[$key] = $star['sort'];
        }
        array_multisort($starList, SORT_ASC, SORT_NUMERIC, $starSort);
        $newSuperTopicInfo['starList'] = $starList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getHotList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $hotList = json_decode($relation['data'], true);
        $hotList = is_null($hotList) ? [] : $hotList;
        $hotSort = [];
        foreach ($hotList as $key => &$hot)
        {
            $hotSort[$key] = $hot['sort'];
            $hot['startTime'] = date('Y-m-d', $hot['startTime']);
            $iconArr = json_decode($hot['icon'], true);
            $hot['icon'] = isset($iconArr[0]) ? $iconArr[0] : '';
        }
        array_multisort($hotList, SORT_ASC, SORT_NUMERIC, $hotSort);

        $newSuperTopicInfo['hotActivityList']['title'] = $relation['title'];
        $newSuperTopicInfo['hotActivityList']['hotList'] = $hotList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getLiveList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $liveList = json_decode($relation['data'], true);
        $liveList = is_null($liveList) ? [] : $liveList;
        $liveSort = [];
        foreach ($liveList as $key => $live)
        {
            $liveSort[$key] = $live['sort'];
        }
        array_multisort($liveList, SORT_ASC, SORT_NUMERIC, $liveSort);

        $newSuperTopicInfo['liveInterViewList']['title'] = $relation['title'];
        $newSuperTopicInfo['liveInterViewList']['liveList'] = $liveList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getTopicList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $topicList = json_decode($relation['data'], true);
        $topicList = is_null($topicList) ? [] : $topicList;
        $topicSort = [];
        foreach ($topicList as $key => $topic)
        {
            $topicSort[$key] = $topic['sort'];
        }
        array_multisort($topicList, SORT_ASC, SORT_NUMERIC, $topicSort);

        $newSuperTopicInfo['featuredTopicList']['title'] = $relation['title'];
        $newSuperTopicInfo['featuredTopicList']['topicList'] = $topicList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getTravelList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $travelList = json_decode($relation['data'], true);
        $travelList = is_null($travelList) ? [] : $travelList;
        $travelSort = [];
        foreach ($travelList as $key => $travel)
        {
            $travelSort[$key] = $travel['sort'];
        }
        array_multisort($travelList, SORT_ASC, SORT_NUMERIC, $travelSort);

        $newSuperTopicInfo['travelSceneList']['title'] = $relation['title'];
        $newSuperTopicInfo['travelSceneList']['travel'] = $travelList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getTopicHeatingList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $topicHeatingList = json_decode($relation['data'], true);
        $topicHeatingList = is_null($topicHeatingList) ? [] : $topicHeatingList;
        $topicHeatingSort = [];
        foreach ($topicHeatingList as $key => $topicHeating)
        {
            $topicHeatingSort[$key] = $topicHeating['sort'];
        }
        array_multisort($topicHeatingList, SORT_ASC, SORT_NUMERIC, $topicHeatingSort);

        $newSuperTopicInfo['topicHeatingList']['title'] = $relation['title'];
        $newSuperTopicInfo['topicHeatingList']['topicHeating'] = $topicHeatingList;
        return $newSuperTopicInfo;
    }/*}}}*/

    public static function getTopicalInformationList($newSuperTopicInfo, $relation)
    {/*{{{*/
        $topicalInformationList = json_decode($relation['data'], true);
        $topicalInformationList = is_null($topicalInformationList) ? [] : $topicalInformationList;
        $topicalInformationSort = [];
        foreach ($topicalInformationList as $key => $topicalInformation)
        {
            $topicalInformationSort[$key] = $topicalInformation['sort'];
        }
        array_multisort($topicalInformationList, SORT_ASC, SORT_NUMERIC, $topicalInformationSort);

        $newSuperTopicInfo['topicalInformationList']['title'] = $relation['title'];
        $newSuperTopicInfo['topicalInformationList']['topicalInformation'] = $topicalInformationList;
        return $newSuperTopicInfo;
    }/*}}}*/

}
