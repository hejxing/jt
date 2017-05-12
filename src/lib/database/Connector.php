<?php
/**
 * Created by PhpStorm.
 * User: ax
 * Date: 2017/5/2 14:02
 */

namespace jt\lib\database;

use jt\compile\config\Database;
use jt\Exception;

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
     * 生成sql语句时所用的引号
     *
     * @type array
     */
    protected static $quotesList = [
        'mysql' => '`',
        'pgsql' => '"'
    ];

    public function __construct($module, $conn)
    {
        $this->connSeed = $module.$conn;
        $this->config   = self::loadConfig($module, $conn);

        if(!isset(self::$quotesList[$this->config['type']])){
            throw new \ErrorException('DatabaseTypeIll:数据库类型 ['.$this->config['type'].'] 错误，不存在此种数据库或未实现对此种数据库的支持');
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
     * @param bool $persistent 是否建立长连接
     * @return \PDO
     * @throws \ErrorException
     */
    protected function createPDO($persistent = true)
    {
        $pdo = new \PDO($this->generateDsn(), $this->config['user'], $this->config['password'], [
            \PDO::ATTR_EMULATE_PREPARES => true,//不使用数据库提供的prepares
            \PDO::ATTR_PERSISTENT       => $persistent,//长连接
            \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,//抛出异常
            \PDO::ATTR_TIMEOUT          => $this->config['timeout']
        ]);
        return $pdo;
    }

    /**
     * 打开一个连接
     *
     * @return \PDO
     */
    public function open()
    {
        if(empty(self::$pdoList[$this->connSeed])){
            self::$pdoList[$this->connSeed] = $this->createPDO();
        }
        /** @type \PDO $pdo */
        $pdo = self::$pdoList[$this->connSeed];

        if($this->config['transaction'] && !$pdo->inTransaction()){
            $pdo->beginTransaction();
        }

        return $pdo;
    }

    /**
     * 加载数据库配置
     *
     * @param $module
     * @param $conn
     * @return array
     * @throws \ErrorException
     */
    protected static function loadConfig($module, $conn)
    {
        $connSeed = $module.$conn;
        if(isset(static::$configPool[$connSeed])){
            return static::$configPool[$connSeed];
        }
        //获取模块配置
        if(isset(static::$configModelPool[$module])){
            $modelConfig = static::$configModelPool[$module];
        }else{
            $modelConfig                      = static::readConfig();
            static::$configModelPool[$module] = $modelConfig;
        }
        if(isset($modelConfig[$conn])){
            $config = \array_replace_recursive($modelConfig['__base'], $modelConfig[$conn]);
        }else{
            throw new \ErrorException('ModelConnIll:数据库连接类型 ['.$conn.'] 的配置不存在或不正确，请检查');
        }

        static::$configPool[$connSeed] = $config;

        return $config;
    }

    /**
     * 加载配置文件
     *
     * @return mixed
     * @throws Exception
     */
    public static function readConfig()
    {
        $file = RUNTIME_PATH_ROOT.'/config/db_'.MODULE.'.php';
        if(!file_exists($file) || RUN_MODE === 'develop'){
            Database::general($file, PROJECT_ROOT.'/config/'.RUN_MODE.'/Database.php');
        }

        /** @noinspection PhpIncludeInspection */
        return include($file);
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

        return \call_user_func_array([$this, $config['type']], [$config]);
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
        $dsn = 'mysql:host='.$config['host'];
        if($this->selectDb){
            $dsn .= ';dbname='.$this->config['dBPrefix'].$config['schema'];
        }
        if(isset($config['port'])){
            $dsn .= ';port='.$config['port'];
        }
        if(isset($config['charset'])){
            $dsn .= ';charset='.$config['charset'];
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
        $dsn = 'pgsql:host='.$config['host'];
        if($this->selectDb){
            $dsn .= ';dbname='.$this->config['dBPrefix'].$config['schema'];
        }
        if(isset($config['port'])){
            $dsn .= ';port='.$config['port'];
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