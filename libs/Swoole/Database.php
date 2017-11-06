<?php
namespace Swoole;
use Swoole;
/**
 * 数据库基类
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage database
 */

/**
 * Database Driver接口
 * 数据库驱动类的接口
 * @author Tianfeng.Han
 *
 */
interface IDatabase
{
	function query($sql);
	function connect();
	function close();
	function lastInsertId();
    function getAffectedRows();
    function errno();
    function quote($str);
}
/**
 * Database Driver接口
 * 数据库结果集的接口，提供2种接口
 * fetch 获取单条数据
 * fetch 获取全部数据到数组
 * @author Tianfeng.Han
 */
interface IDbRecord
{
	function fetch();
	function fetchall();
}

/**
 * Database类，处理数据库连接和基本的SQL组合
 * 提供4种接口，query  insert update delete
 * @method connect
 * @method close
 * @method quote $str
 * @method errno
 */
class Database
{
	public $debug = false;
	public $read_times = 0;
	public $write_times = 0;

    /**
     * @var IDatabase
     */
	public $_db = null;
    /**
     * @var \Swoole\SelectDB
     */
    public $db_apt = null;

    protected $lastSql = '';

	const TYPE_MYSQL   = 1;
	const TYPE_MYSQLi  = 2;
	const TYPE_PDO     = 3;
    const TYPE_CLMysql = 4;

    function __construct($db_config)
    {
        switch ($db_config['type'])
        {
            case self::TYPE_MYSQL:
                $this->_db = new Database\MySQL($db_config);
                break;
            case self::TYPE_MYSQLi:
                $this->_db = new Database\MySQLi($db_config);
                break;
            case self::TYPE_CLMysql:
                $this->_db = new Database\CLMySQL($db_config);
                break;
            default:
                $this->_db = new Database\PdoDB($db_config);
                break;
        }
    }
    /**
     * 调用$driver的自带方法
     * @param $method
     * @param array $args
     * @return mixed
     */
    function __call($method, $args = array())
    {
        try {
            return @call_user_func_array(array($this->_db, $method), $args);
        } catch(\Exception $e)
        {
            Swoole::getInstance()->log->error(__FILE__ . "\t" . __LINE__ . "\t" . $e->getTraceAsString());
            return false;
        }
    }
}
