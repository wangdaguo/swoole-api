<?php
namespace Swoole;

/**
 * Model类，ORM基础类，提供对某个数据库表的接口
 * @author Tianfeng Han
 * @package SwooleSystem
 * @subpackage Model
 * @link http://www.swoole.com/
 */
class Model
{
    /**
     * @var IDatabase
     */
	public $swoole;

	public $primary = "id";

	/**
	 * 表切片参数
	 *
	 * @var int
	 */
    public $tablesize = 1000000;



    /**
     * 构造函数
     * @param \Swoole $swoole
     * @param string $db_key 选择哪个数据库
     */
    function __construct(\Swoole $swoole, $db_key = 'master')
    {
        $this->swoole = $swoole;
    }

}
