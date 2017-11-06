<?php
namespace apps\models;
use apps\classes\LibShmCache;

class VoteQuestion extends BaseModel
{
    const DB_CACHE_EXPIRE = 10; // db query cache 10s

    const STATUS_VALID = 1;
    const STATUS_INVALID = 0;

    public static function tableName()
    {/*{{{*/
        return 'vote_question';
    }/*}}}*/

    public static function getVoteInfo($id)
    {/*{{{*/
        $activityCommon = ActivityCommon::getInfo($id);
        $question = self::getInfo($id);
        $options = VoteOption::getList($id);
        $data['voteInfo']['id'] = $activityCommon['id'];
        $data['voteInfo']['title'] = $activityCommon['title'];
        $data['voteInfo']['linkTitle'] = $question['title'];
        $data['voteInfo']['type'] = $question['type'];
        $data['voteInfo']['images'] = $question['images'];
        $data['voteInfo']['creator'] = $activityCommon['publisher'];
        $data['voteInfo']['content'] = $question['content'];
        $data['voteInfo']['showTime'] = date('Y-m-d',strtotime($activityCommon['updateTime']));
        $data['voteInfo']['participateNum'] = $activityCommon['number'];
        $data['voteInfo']['maxOptionNum'] = $question['maxOptionNum'];
        $data['voteInfo']['endTime'] = intVal($activityCommon['endTime']);
        $data['voteInfo']['optionSum'] = $options['sum'];
        $data['voteInfo']['currentTime'] = time();
        $data['optionList'] = $options['list'];
        if($question['images'])
        {
            $data['banner'] = json_decode($question['images'], 1);
        } else {
            $data['banner'] = [];
        }
        return $data;

    }/*}}}*/

    public static function getInfo($id, $useCache = true)
    {/*{{{*/
        $localShmCacheKey = 'vote_question_' . $id;
        $cacheKey  = 'vote_question_' . $id;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $info = LibShmCache::get($localShmCacheKey);
            if($info)
            {
                return $info;
            }
            $info = $mcClient->get($cacheKey);
            if($info)
            {
                LibShmCache::set($localShmCacheKey, $info, 10);
                return $info;
            }
        }
        $sql = 'select * from ' . self::tableName() . ' where id = ' . intval($id);
        $info = self::getSlaveClusterDb()->queryOne($sql);

        if($info)
        {
            $mcClient->set($cacheKey, $info, 300);
            LibShmCache::set($localShmCacheKey, $info, 10);
        }
        return $info;

    }/*}}}*/


    public static function formatOptionInfo($optionInfoArr)
    {/*{{{*/
        $data = [];
        $sum = 0;
        foreach ($optionInfoArr as $optionInfo) {
            $info = [
                'id' => $optionInfo['opid'],
                'vid' => $optionInfo['questionId'],
                'optionCount' => $optionInfo['count'],
                'optionContent' => $optionInfo['optionContent'],
                'img' => $optionInfo['img'],
            ];
            $sum += $optionInfo['count'];
            $data[] = $info;
            $data['sum'] = $sum;
        }
        return $data;
    }/*}}}*/


    public static function vote($vid, $uid, $maxOptionNum, array $options, $userInfo)
    {/*{{{*/
        $connection = self::getMasterDb();
        if($connection->beginTransaction())
        {
            $optionsCount = count($options);
            try {
                //update sql
                $updateActivityCommonSql = "update " . ActivityCommon::tableName() . " set number=number+1 where id = :vid and endTime >= :endTime";
                $bindsActivityCommon = [
                    ":vid" => $vid,
                    ":endTime" => time(),
                ];
                $updateActivityCommonResult = $connection->execute($updateActivityCommonSql, $bindsActivityCommon);

                $updateVoteQuestionSql = "update " . self::tableName() . " set participateNum=participateNum+1 where id = :vid and maxOptionNum >= :optionCount ";
                $bindsQuestion = [
                    ":vid" => $vid,
                    ":optionCount" => $optionsCount,
                ];
                $updateQuestionResult = $connection->execute($updateVoteQuestionSql, $bindsQuestion);

                $optionIdStr = implode(',', $options);
                $updateVoteOptionSql = " update " . VoteOption::tableName() . " set count=count+1 where questionId = :questionId and id in ($optionIdStr)";
                $bindsOption = [
                    ":questionId" => $vid
                ];
                $updateOptionResult = $connection->execute($updateVoteOptionSql, $bindsOption);

                $voteParticipateSaveResult = VoteParticipate::saveData($vid, $uid, $options, $userInfo);

                if (!$updateActivityCommonResult || !$updateQuestionResult || !$updateOptionResult || !$voteParticipateSaveResult || count($options) !== $updateOptionResult) {
                    throw new \Exception("result failed");
                }

                $connection->commit();
                ActivityCommon::getInfo($vid, false);
                VoteOption::getList($vid, false);
                VoteParticipate::getListFromMc($vid, 1, 50, 0, false);
                return true;
            } catch (\Exception $e) {
                $connection->rollBack();
                return false;
            }
        }
    }/*}}}*/

    public static function deleteInfoCache($vid)
    {/*{{{*/
        $mcClient = self::getMcClient();
        $key = self::getInfoMcKey($vid);
        return $mcClient->delete($key);
    }/*}}}*/

    public static function clearCache($params)
    {/*{{{*/
        $mcClient = self::getMcClient();
        $key = self::getInfoMcKey($params['vid']);
        return $mcClient->delete($key);
    }/*}}}*/

}
