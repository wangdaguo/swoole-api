<?php
define('WEBPATH', __DIR__);
require WEBPATH . '/libs/lib_config.php';

//设置PID文件的存储路径
Swoole\Network\Server::setPidFile(__DIR__ . '/built_webserver.pid');
/**
 * 显示Usage界面
 * php app_server.php start|stop|reload
 */
Swoole\Network\Server::start(function ()
{
    $daemonize = defined('DEBUG') && DEBUG == 'on' ? 0 : 1;
    $config = array(
        'document_root' => WEBPATH,
        'log_file' => '/data/logs/fantuan/swoole.log',
        'charset' => 'UTF-8',
        'worker_num' => 1,
        'keepalive' => 1,
        'max_request' => 2000,
        'heartbeat_idle_time'=>120,
        'heartbeat_check_interval' => 60,
//        'daemonize' => $daemonize
    );
    Swoole::$php->runHttpServer('0.0.0.0', 9501, $config);
});
