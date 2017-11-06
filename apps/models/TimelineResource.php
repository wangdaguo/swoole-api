<?php
namespace apps\models;

use apps\models\BaseModel;
use Swoole;
class TimelineResource extends BaseModel
{
    public static function sepTableName($sid)
    {/*{{{*/
        return 'timeline_resource_' . $sid % 100;
    }/*}}}*/

    public  static function getList($sid, $timelineIdArr)
    {/*{{{*/
        $sql = 'select timelineId, video_url as videourl, image_url as imageurl, type 
            from ' . self::sepTableName($sid) . '
            where timelineId in ('.implode(',', $timelineIdArr) .')';
		$list = Swoole::getInstance()->db('fantuan_slave')->queryAll($sql);
		$formatList = [];
		foreach ($list as $resource)
		{
			if($resource['imageurl'])
			{
				$resource['imageurl'] = str_replace('thumbnail', 'big', $resource['imageurl']);
                $resource['imageurl'] = str_replace('g.cdn.upliveapps.com', 'g.cdn.upliveapp.com', $resource['imageurl']);
			}
			if($resource['videourl'])
            {
                $resource['videourl'] = str_replace('%2F', '/', $resource['videourl']);
                $resource['videourl'] = str_replace('g.cdn.upliveapps.com', 'g.cdn.upliveapp.com', $resource['videourl']);
            }
			
			$formatList[$resource['timelineId']][] = $resource;
		}
		return $formatList;
    }/*}}}*/
}
