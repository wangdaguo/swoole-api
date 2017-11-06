<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
global $php;
try{
 $db = $php->db('fantuan_slave');
 while(true)
 {

     $sql = 'select * from timeline order by id desc limit 1';
     $result = $db->queryOne($sql);
     var_dump($result);
     sleep(1);
 }
}catch(\Exception $e)
{
    var_dump($e->getCode());
    echo "\n";
    var_dump($e->getMessage());
}
