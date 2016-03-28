<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/22
 * Time: 14:02
 */

namespace jt\database;

use jt\Error;
use jt\exception\TaskException;

class Connector
{
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
     * 生成当前连接的标识
     *
     * @type string
     */
    protected $connSeed = '';
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

    protected static $quotesList = [
        'mysql' => '`',
        'pgsql' => '"'
    ];

    public function __construct($module, $conn)
    {
        $this->connSeed = $module . $conn;
        $this->config   = self::loadConfig($module, $conn);
        if (!isset(self::$quotesList[$this->config['type']])) {
            throw new \ErrorException('DatabaseTypeIll:数据库类型 [' . $this->config['type'] . '] 错误，不存在此种数据库或未实现对此种数据库的支持');
        }
    }

    /**
     * 构建Sql时对表名，字段名所用的引号
     *
     * @return mixed
     */
    public function getQuotes()
    {
        return self::$quotesList[$this->config['type']];
    }

    /**
     * 创建PDO
     *
     * @return \PDO
     * @throws \ErrorException
     */
    protected function createPDO()
    {
        $pdo = new \PDO($this->generateDsn(), $this->config['user'], $this->config['password']);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true); //不使用数据库提供的prepares
        $pdo->setAttribute(\PDO::ATTR_PERSISTENT, true); //长连接
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); //抛出异常
        $pdo->setAttribute(\PDO::ATTR_TIMEOUT, $this->config['timeout']);

        //$pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        return $pdo;
    }

    /**
     * 打开一个连接
     *
     * @return \PDO
     */
    public function open()
    {
        if (isset(self::$pdoList[$this->connSeed])) {
            $pdo = self::$pdoList[$this->connSeed];
        }else {
            $pdo                            = $this->createPDO();
            self::$pdoList[$this->connSeed] = $pdo;
        }
        if (!$pdo->inTransaction()) {
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
    protected static function loadConfig($module, $conn)
    {
        $connSeed = $module . $conn;
        if (isset(static::$configPool[$connSeed])) {
            return static::$configPool[$connSeed];
        }

        //获取全局配置
        if (empty(static::$baseConfig)) {
            static::$baseConfig = static::readConfig($module . 'config/database.php');
        }
        //获取模块配置
        if (isset(static::$configModelPool[$module])) {
            $modelConfig = static::$configModelPool[$module];
        }else {
            $modelConfig                      = static::readConfig($module . 'config/' . RUN_MODE . '/database.php');
            $modelConfig                      = \array_replace_recursive(static::$baseConfig, $modelConfig);
            static::$configModelPool[$module] = $modelConfig;
        }

        $config                        = \array_replace_recursive($modelConfig['__base'], $modelConfig[$conn]);
        static::$configPool[$connSeed] = $config;

        return $config;
    }

    /**
     * 加载配置文件
     *
     * @param $file
     *
     * @return mixed
     * @throws TaskException
     */
    protected static function readConfig($file)
    {
        $result = include(PROJECT_ROOT . '/' . $file);

        if ($result === false) {
            throw new TaskException('databaseConfigNotFound: 数据库配置文件 [' . $file . '] 不存在');
        }

        return $result;
    }

    /**
     * 生成PDO实例
     *
     * @throws \ErrorException
     * @return string
     */
    private function generateDsn()
    {
        $config = $this->config;

        try{
            return \call_user_func_array([$this, $config['type']], [$config]);
        }catch (\PDOException $e){
            Error::fatal($e->getMessage(), $e->getCode());
        }

        return null;
    }

    /**
     * 生成Mysql的连接字串
     *
     * @param array $config
     *
     * @return string
     */
    protected function mysql(array $config)
    {
        $dsn = 'mysql:host=' . $config['host'];
        if ($this->selectDb) {
            $dsn .= ';dbname=' . $this->config['dBPrefix'] . $config['schema'];
        }
        if (isset($config['port'])) {
            $dsn .= ';port=' . $config['port'];
        }
        if (isset($config['charset'])) {
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
    protected function pgsql(array $config)
    {
        $dsn = 'pgsql:host=' . $config['host'];
        if ($this->selectDb) {
            $dsn .= ';dbname=' . $this->config['dBPrefix'] . $config['schema'];
        }
        if (isset($config['port'])) {
            $dsn .= ';port=' . $config['port'];
        }

        return $dsn;
    }

    /**
     * 获取连接列表
     *
     * @return array[\PDO] $pdoList
     */
    public static function getPdoList()
    {
        return self::$pdoList;
    }

    /**
     * 获取表前缀
     *
     * @return mixed
     */
    public function getTablePrefix()
    {
        return $this->config['tablePrefix'];
    }

    /**
     * 获取的连接的数据库类型
     *
     * @return string
     */
    public function getDatabaseType()
    {
        return $this->config['type'];
    }
}