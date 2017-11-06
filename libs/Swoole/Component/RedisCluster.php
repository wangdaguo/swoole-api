<?php
namespace Swoole\Component;
use Swoole;

/**
 * Class Redis
 * @package Swoole\Component
 */
class RedisCluster
{
    const READ_LINE_NUMBER = 0;
    const READ_LENGTH = 1;
    const READ_DATA = 2;

    /**
     * @var \Redis
     */
    protected $_redis;

    public $config;


    function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    function connect()
    {
        try
        {
            if ($this->_redis) {
                unset($this->_redis);
            }
            $config = [];
            foreach ($this->config['servers'] as $server)
            {
                $config[] = $server['host'] . ':' . $server['port'];
            }
            $config[] = $this->config['timeout'];
            $config[] = $this->config['readTimeout'];
            $config[] = $this->config['pconnect'];
            $this->_redis = new \RedisCluster(NULL, $config);
        }
        catch (\RedisException $e)
        {
            \Swoole::$php->log->error(__CLASS__ . " Swoole Redis Exception" . var_export($e, 1));
            return false;
        }
    }

    function __call($method, $args = array())
    {
        $reConnect = false;
        while (1)
        {
            try
            {
                $result = call_user_func_array(array($this->_redis, $method), $args);
            }
            catch (\RedisException $e)
            {
                //已重连过，仍然报错
                if ($reConnect)
                {
                    throw $e;
                }

                \Swoole::$php->log->error(__CLASS__ . " [" . posix_getpid() . "] Swoole Redis Exception(Msg=" . $e->getMessage() . ", Code=" . $e->getCode() . "), Redis->{$method}, Params=" . var_export($args, 1));
                $this->_redis->close();
                $this->connect();
                $reConnect = true;
                continue;
            }
            return $result;
        }
        //不可能到这里
        return false;
    }


}
