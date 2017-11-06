<?php
$db = [];
switch(APP_ENV)
{
    case 'dev':
        $db['fantuan_master'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "10.0.1.19",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "php_root",
            'passwd'     => "php@root",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['fantuanSlaveClusterDb'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "10.0.1.19",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "php_root",
            'passwd'     => "php@root",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        $db['fantuan_slave'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "10.0.1.19",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "php_root",
            'passwd'     => "php@root",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['starbase'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "10.0.1.19",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "php_root",
            'passwd'     => "php@root",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        break;
    case 'stage':
        $db['fantuan_master'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "other-db",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "fantuan",
            'passwd'     => "stagefantuan",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['fantuanSlaveClusterDb'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "other-db",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "fantuan",
            'passwd'     => "stagefantuan",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        $db['fantuan_slave'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "other-db",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "fantuan",
            'passwd'     => "stagefantuan",
            'name'       => "fantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['starbase'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "usercenter-db",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "starbasedb",
            'passwd'     => "stagestarbasedb",
            'name'       => "starbasedb",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        break;
    default:
        $db['fantuan_master'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "star-mysql-sfantuan",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "sfantuan",
            'passwd'     => "sfantuan@145tysFgldb17",
            'name'       => "sfantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['fantuanSlaveClusterDb'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "star-mysql-read01",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "sfantuanread",
            'passwd'     => "sfanread#@WE145tb17",
            'name'       => "sfantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        $db['fantuan_slave'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "star-mysql-sfantuan-replica01",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "sfantuanread",
            'passwd'     => "sfanread#@WE145tb17",
            'name'       => "sfantuan",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );
        $db['starbase'] = array(
            'type'       => Swoole\Database::TYPE_PDO,
            'host'       => "star-mysql-base",
            'port'       => 3306,
            'dbms'       => 'mysql',
            'engine'     => 'InnoDB',
            'user'       => "starbasedb",
            'passwd'     => "udIDWFGK?Di12",
            'name'       => "starbasedb",
            'charset'    => "utf8mb4",
            'setname'    => true,
            'persistent' => true, //MySQL长连接
        );

        break;
}


return $db;
