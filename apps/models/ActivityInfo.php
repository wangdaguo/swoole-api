<?php
namespace apps\models;
use Swoole;
use apps\classes\LibShmCache;

class ActivityInfo extends BaseModel
{
    function getInfo($id, $useCache = true)
    {/*{{{*/
        
        $cacheKey = 'activity_info_' . $id;
        $mcClient = self::getCacheCluster2Client();
        if($useCache)
        {
            $info = $mcClient->get($cacheKey);
            if($info)
            {
                return $info;
            }
        }
        $info = self::getInfoFromDb($id);
        $info && $mcClient->set($cacheKey, $info, 300);
        return $info;
    }/*}}}*/

    public static function getInfoFromDb($id)
    {/*{{{*/
        $sql = 'select * from ' . self::tableName() . ' where id=' . intval($id);
        return self::getSlaveClusterDb()->queryOne($sql);
    }/*}}}*/


    public static function tableName()
    {/*{{{*/
        return 'activity_list';
    }/*}}}*/

}
