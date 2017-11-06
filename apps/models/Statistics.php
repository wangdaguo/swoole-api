<?php
namespace apps\models;

class Statistics extends BaseModel
{
    public static function tableName()
    {/*{{{*/
        return 'system_stats_rpm';
    }/*}}}*/

    public static function saveData($route, $time, $number, $hostname)
    {
        $sql = 'insert into ' . self::tableName() . '
            (route, time, number, hostname) values 
            (:route, :time, :number,:hostname)';
        $binds = [
            ':route' => $route,
            ':time' => $time,
            ':number' => $number,
            ':hostname' => $hostname,
        ];
        return self::getMasterDb()->execute($sql, $binds);
    }
}
