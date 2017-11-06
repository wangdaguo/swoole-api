<?php
namespace Swoole\Database;
use Swoole;

/**
 * PDO数据库封装类
 * @package SwooleExtend
 * @author Tianfeng.Han
 *
 */
class PdoDB extends \PDO implements Swoole\IDatabase
{
	public $debug = false;
    protected $config;

    /**
     * @var \PDOStatement
     */
    protected $lastStatement;

	function __construct($db_config)
	{
        $this->config = $db_config;
	}

    /**
     * 连接到数据库
     */
    function connect()
    {
        try {
            $db_config = &$this->config;
            $dsn = $db_config['dbms'] . ":host=" . $db_config['host'] . ";dbname=" . $db_config['name'];

            if (!empty($db_config['persistent']))
            {
                parent::__construct($dsn, $db_config['user'], $db_config['passwd'], array(\PDO::ATTR_PERSISTENT => true));
            }
            else
            {
                parent::__construct($dsn, $db_config['user'], $db_config['passwd']);
            }
            if ($db_config['setname'])
            {
                parent::query('set names ' . $db_config['charset']);
            }
            $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch(\Exception $e)
            {
                trigger_error("Cannot connect DB", E_USER_ERROR);
            }
    }

	/**
	 * 执行一个SQL语句
	 * @param string $sql 执行的SQL语句
     * @return \PDOStatement
     */
    public final function query($sql)
    {
        $try = 0;
        
        for($i = 0; $i < 2; $i++)
        {
            $res = parent::query($sql);
            if($res)
            {
                $this->lastStatement = $res;
                //非查询语句直接返回结果
                if ($sql[0] !== 's')
                {
                    return !empty($res);
                }
                return $res;
            } else {
                $errorInfo = $stm->errorInfo();
                Swoole::getInstance()->log->error($sql . json_encode($params) . json_encode($errorInfo));
                if($try == 0)
                {
                    $this->tryReconnect($errorInfo);
                    $try = 1;
                }
            }
        }
        return false;
    }

    /**
     * 执行一个参数化SQL语句,并返回一行结果
     * @param string $sql 执行的SQL语句
     * @param  mixed $_     [optional]
     * @return mixed
     */
    public final function queryOne($sql, $_ = [])
    {
        $params = $_;
        $try = 0;
        for($i = 0; $i < 2; $i++)
        {
            $stm = $this->prepare($sql);
            if ($stm->execute($params))
            {
                $ret = $stm->fetch();
                $stm->closeCursor();
                return $ret;
            } else{
                $errorInfo = $stm->errorInfo();
                Swoole::getInstance()->log->error( $sql . json_encode($params) . json_encode($errorInfo));
                if($try == 0 && $this->isDisconnection($errorInfo))
                {
                    $this->connect();
                    $try = 1;
                }else{
                    break;
                }
            }
        }
        return false;
    }

    /**
     * 执行一个参数化SQL语句,并返回所有结果
     * @param string $sql 执行的SQL语句
     * @param  mixed $_     [optional]
     * @return mixed
     */
    public final function queryAll($sql, $_ = [])
    {
        $params = $_;
        $try = 0;
        for($i = 0; $i < 2; $i++)
        {
            $stm = $this->prepare($sql);
            if ($stm->execute($params))
            {
                $ret = $stm->fetchAll();
                $stm->closeCursor();
                return $ret;
            }
            else
            {
                $errorInfo = $stm->errorInfo();
                Swoole::getInstance()->log->error( $sql . json_encode($params) . json_encode($errorInfo));
                if($try == 0 && $this->isDisconnection($errorInfo))
                {
                    $this->connect();
                    $try = 1;
                }else{
                    break;
                }
            }
        }
        return false;
    }

    /**
     * 执行一个参数化SQL语句
     * @param string $sql 执行的SQL语句
     * @param  mixed $_     [optional]
     * @return int          last insert id
     */
    public final function execute($sql, $_ = [])
    {
        $params = $_;
        $try = 0;
        for($i = 0; $i < 2; $i++)
        {
            $stm = $this->prepare($sql);
            if ($stm->execute($params))
            {
                $sql = trim($sql);
                if(strtolower($sql[0]) == 'i')
                {
                    return $this->lastInsertId();
                } else {
                    return $stm->rowCount();
                }

            }
            else
            {
                $errorInfo = $stm->errorInfo();
                Swoole::getInstance()->log->error( $sql . json_encode($params) . json_encode($errorInfo));
                if($try == 0 && $this->isDisconnection($errorInfo))
                {
                    $this->connect();
                    $try = 1;
                }else{
                    break;
                }

            }
        }
        return false;
    }

    /**
     * 获取错误码
     */
    function errno()
    {
        $this->errorCode();
    }

    /**
     * 获取受影响的行数
     * @return int
     */
    function getAffectedRows()
    {
        return $this->lastStatement ? $this->lastStatement->rowCount() : false;
    }

    /**
	 * 关闭连接，释放资源
	 * @return null
	 */
	function close()
	{
		//unset($this);
		return;
	}

    function isDisconnection($errorInfo)
    {

        if($errorInfo[1] && ($errorInfo[1] == 2006 or $errorInfo[1] == 2013))
        {
            return true;
        }
        return false;
    }

	/*
	function quote($str,$paramtype = NULL)
    {
        $safeStr = parent::quote($str);
        return substr($safeStr, 1, strlen($safeStr) - 2);
    }
	 */
}
