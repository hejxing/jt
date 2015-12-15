<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/22
 * Time: 14:02
 */

namespace jt\database;

use \jt\Bootstrap;
use jt\Error;

class Connector extends \PDO{
	/**
	 * 已经打开的数据库连接列表
	 *
	 * @type array[]
	 */
	private static $pdoList = [];
	/**
	 * 缓存配置
	 *
	 * @type array
	 */
	protected static $configPool = [];
	/**
	 * 缓存模块配置
	 *
	 * @type array
	 */
	protected static $configModelPool = [];
	/**
	 * 数据库配置
	 *
	 * @type array
	 */
	protected $config = [];
	/**
	 * 是否连接数据库
	 *
	 * @type bool
	 */
	protected $selectDb = true;
	/**
	 * 缓存已经生成的连接
	 *
	 * @type array
	 */
	protected static $connPool = [];
	/**
	 * 基础配置,缓存在此处
	 *
	 * @type array
	 */
	protected static $baseConfig = [];

	public function __construct($module, $conn){
		$this->config = self::loadConfig($module, $conn);
		parent::__construct($this->generateDsn(), $this->config['user'], $this->config['password']);
		$this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true); //不使用数据库提供的prepares
		$this->setAttribute(\PDO::ATTR_PERSISTENT, true); //长连接
		$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); //抛出异常
		//$this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
	}

	/**
	 * 打开一个连接
	 *
	 * @param string $module 当前所在模块
	 * @param string $conn 数据库连接名
	 *
	 * @return Connector
	 */
	public static function open($module, $conn){
		$connSeed = $module . '/' . $conn;
		if (isset(self::$pdoList[$connSeed])){
			$pdo = self::$pdoList[$connSeed];
		}else{
			$pdo = new static($module, $conn);
			self::$pdoList[$connSeed] = $pdo;
		}
		if (!$pdo->inTransaction()){
			$pdo->beginTransaction();
		}

		return $pdo;
	}

	/**
	 * 加载数据库配置
	 *
	 * @param $module
	 * @param $conn
	 *
	 * @return array
	 */
	protected static function loadConfig($module, $conn){
		$connSeed = $module . '/' . $conn;
		if (isset(static::$configPool[$connSeed])){
			return static::$configPool[$connSeed];
		}

		//获取全局配置
		if (empty(static::$baseConfig)){
			static::$baseConfig = static::readConfig($module.'/config/database.php');
		}
		//获取模块配置
		if (isset(static::$configModelPool[$module])){
			$modelConfig = static::$configModelPool[$module];
		}else{
			$modelConfig = static::readConfig($module.'/config/'.RUN_MODE.'/database.php');
			$modelConfig = \array_replace_recursive(static::$baseConfig, $modelConfig);
			static::$configModelPool[$module] = $modelConfig;
		}

		$config = \array_replace_recursive($modelConfig['__base'], $modelConfig[$conn]);
		static::$configPool[$connSeed] = $config;
		return $config;
	}

	/**
	 * 加载配置文件
	 *
	 * @param $file
	 *
	 * @return mixed
	 * @throws \ErrorException
	 */
	protected static function readConfig($file){
		$result = include(CORE_ROOT .'/'. $file);
		if ($result === false){
			Error::fatal('404', '文件：' . $file . ' 不存在');
		}

		return $result;
	}

	/**
	 * 生成PDO实例
	 *
	 * @throws \ErrorException
	 * @return string
	 */
	private function generateDsn(){
		$config = $this->config;
		if (\method_exists($this, $config['type'])){
			try{
				return \call_user_func_array([$this, $config['type']], [$config]);
			}catch (\PDOException $e){
				Error::fatal($e->getMessage(), $e->getCode());
			}
		}else{
			throw new \ErrorException('数据库类型 [' . $config['type'] . '] 不存在');
		}
	}

	/**
	 * 生成Mysql的连接字串
	 *
	 * @param array $config
	 *
	 * @return string
	 */
	protected function mysql(array $config){
		$dsn = 'mysql:host=' . $config['host'];
		if ($this->selectDb){
			$dsn .= ';dbname=' . $this->config['dBPrefix'] . $config['schema'];
		}
		if (isset($config['port'])){
			$dsn .= ';port=' . $config['port'];
		}
		if (isset($config['charset'])){
			$dsn .= ';charset=' . $config['charset'];
		}

		return $dsn;
	}

	/**
	 * 生成PostgreSQL的连接字串
	 *
	 * @param array $config
	 *
	 * @return string
	 */
	protected function pgsql(array $config){
		$dsn = 'pgsql:host=' . $config['host'];
		if ($this->selectDb){
			$dsn .= ';dbname=' . $this->config['dBPrefix'] . $config['schema'];
		}
		if (isset($config['port'])){
			$dsn .= ';port=' . $config['port'];
		}

		return $dsn;
	}

	/**
	 * 获取连接列表
	 *
	 * @return array[\PDO] $pdoList
	 */
	public static function getPdoList(){
		return self::$pdoList;
	}

	/**
	 * 获取表前缀
	 *
	 * @param $module
	 * @param $conn
	 *
	 * @return mixed
	 */
	public static function getTablePrefix($module, $conn){
		$config = self::loadConfig($module, $conn);
		return $config['tablePrefix'];
	}
}