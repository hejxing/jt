<?php

/**
 *
 * Copyright 2015, csmall.com
 */

namespace jt;

use jt\lib\database\Connector;
use jt\lib\database\ErrorCode;
use jt\lib\database\Schema;
use jt\utils\Debug;

abstract class Model
{
    const UUID_ZERO = '00000000-0000-0000-0000-000000000000';

    const LIKE_LEFT  = 1;
    const LIKE_RIGHT = 2;
    const LIKE_BOTH  = 3;

    const JOIN_INNER = 'INNER';
    const JOIN_LEFT  = 'LEFT';
    const JOIN_RIGHT = 'RIGHT';
    const JOIN_FULL  = 'FULL';

    const BOUND_NONE  = 0;//不加括号
    const BOUND_BEGIN = 1;//开始一个括号
    const BOUND_END   = 2;//关闭括号
    const BOUND_SELF  = 3;//将自身括起来
    const BOUND_OTHER = 4;//将其它条件括起来
    const BOUND_ALONE = 7;//将自身和其它条件都括起来

    const LINK_AND = 0;
    const LINK_OR  = 1;

    /**
     * @type string 连接名
     */
    protected $conn = 'generic';
    /**
     * @type \PDO
     */
    private $pdo = null;
    /**
     * @type string 当前操作的表
     */
    protected $table = '';
    /**
     * @type string 添加上引号的table
     */
    protected $quotesTable = '';
    /**
     * @type Connector 数据库连接实例
     */
    protected $connector = null;
    /**
     * @type string 表前缀 无需设定，通过配置文件设定
     */
    protected static $prefix = '';
    /**
     * @type array 数据库日志
     */
    protected $logs = [];
    /**
     * @var string 表主键字段名
     */
    protected static $primary = 'id';
    /**
     * @type string 主键类型
     */
    protected static $primaryType = 'uuid';
    /**
     * @type bool 最新插入记录的ID值
     */
    protected $insertId = null;
    /**
     * @type array
     */
    private $lastPageInfo = null;
    /**
     * @var int 每页条数.
     */
    protected $perPage = 15;
    /**
     * @type array 数据库表结构
     */
    protected static $columns = []; //静态属性，便于缓存解析结果
    /**
     * @type array 字段属性映射表
     */
    protected static $fieldMap = [];
    /**
     * @type string 字段名称所用的引号
     */
    protected static $quotes = '"';
    /**
     * @type int 数据库操作次数
     */
    private static $queryTimes = 0;
    /**
     * @type array 搜集的SQL语句
     */
    private $sqlCollect = [];
    /**
     * @type string 生成的PreSql
     */
    private $preSql = '';
    /**
     * @type array 用到的数据
     */
    private $data = [];
    /**
     * @type string 操作中遇到错误时的处理办法 fail,ignore
     */
    private $errorAction = 'fail';
    /**
     * @type array 自定义错误消息列表
     */
    private $errMsgList = [];
    /**
     * @type bool 字段名是否需要限制表名
     */
    private $needFullFieldName = false;
    /**
     * @type array 查询时要取出的字段
     */
    private $queryNames = [];
    /**
     * @type bool 查询完成后是否不清空上次的数据
     */
    protected $isCleanSqlCollect = true;
    /**
     * @type int 查询结果返回形式
     */
    protected $fetch_style = \PDO::FETCH_ASSOC;
    /**
     * @type int 当前查询结果返回形式
     */
    private $currentFetchStyle = 0;
    /**
     * @var bool 条件间是否采用OR连接
     */
    private $connectByOr = false;
    /**
     * @var bool 接下来的条件是否增加not前缀
     */
    private $notCondition = false;
    /**
     * @var bool 是否自动开启事务
     */
    private $restartTransaction = false;
    /**
     * 控制是否输出sql
     *
     * @type bool
     */
    protected static $debugSql = false;
    /**
     * 是否处于调试模式，该模式下不会提交事务
     *
     * @type bool
     */
    protected static $debugMode = false;
    /**
     * 解析表内容用到的值
     *
     * @type array
     */
    protected static $parseDict = [
        'bool'    => ['require', 'increment', 'primary', 'hidden', 'lower', 'del'],
        'type'    => ['uuid', 'timestamp', 'date', 'bit', 'varbit'],
        'string'  => ['char', 'varchar', 'text'],
        'float'   => ['float4', 'float8', 'decimal', 'numeric'],
        'int'     => ['int2', 'int4', 'int8'],
        'serial'  => ['serial2', 'serial4', 'serial8'],
        'boolean' => ['bool'],
        'object'  => ['json', 'jsonb', 'array'],
        'value'   => ['format', 'touch', 'foreign', 'field', 'default', 'validate', 'at', 'filter', 'stuffer'],
        'compare' => ['=', '>', '<', '>=', '<=', '<>', ' like ', ' between ', ' in ']
    ];

    /**
     * 构造Model
     */
    public function __construct()
    {
        static::parseColumns();
        $this->connector = new Connector(PROJECT_ROOT, $this->conn);
        $quotes          = $this->connector->getQuotes();
        static::$quotes  = $quotes;

        $this->table       = $this->connector->getTablePrefix().$this->table;
        $this->quotesTable = $quotes.str_replace('.', "{$quotes}.{$quotes}", $this->table).$quotes;
    }

    /**
     * 模型中字段解析器
     */
    private static function parseColumns()
    {
        if(is_array(current(static::$columns))){
            return;
        }
        $parsed = [];
        foreach(static::$columns as $name => $column){
            $parsed[$name] = self::line($column, $name);
        }
        static::$columns = self::tidyParsed($parsed);
    }

    /**
     * 整理解析结果
     *
     * @param $parsed
     * @return mixed
     */
    private static function tidyParsed($parsed)
    {
        if(!static::$primary){
            self::error('primaryEmpty', '未指定主键');
        }
        if(isset($parsed[static::$primary]['increment'])){
            static::$primaryType = 'increment';
        }

        return $parsed;
    }

    /**
     * 整理解析的一行结果
     *
     * @param $lined
     */
    private static function tidyParsedLine(&$lined)
    {
        if(!isset($lined['type']) && (isset($lined['createAt']) || isset($lined['updateAt']))){
            $lined['type'] = 'timestamp';
        }
        if($lined['type'] === 'array' && empty($lined['typeField'])){
            $lined['typeField'] = 'text';
        }
        //fieldType不允许为空
        if(empty($lined['type'])){
            self::error('typeEmpty', '未指定字段类型');
        }

        //将del行默认设为hidden
        if(!empty($lined['del']) && !isset($lined['hidden'])){
            $lined['hidden'] = true;
        }
    }

    /**
     * 解析一行
     *
     * @param $str
     * @param $name
     * @return array
     */
    private static function line($str, $name)
    {
        $lined = [];
        $parts = \preg_split('/ +/', $str);
        foreach($parts as $a){
            self::attr($a, $name, $lined);
        }
        self::tidyParsedLine($lined);

        return $lined;
    }

    /**
     * 解析规则
     *
     * @param       $a
     * @param       $name
     * @param array $result
     * @return array
     * @throws \jt\Exception
     */
    private static function attr($a, $name, &$result)
    {
        if(\strpos($a, ':')){
            list($key, $value) = \explode(':', $a, 2);
        }else{
            list($key, $value) = [$a, null];
        }

        switch(true){
            case in_array($key, self::$parseDict['bool']):
                if($key === 'primary'){
                    static::$primary = $name;
                }
                if($key === 'del'){
                    $result['type'] = 'bool';
                }
                $result[$key] = true;
                break;
            case in_array($key, self::$parseDict['type']):
                $result['type']   = $key;
                $result['length'] = intval($value);
                break;
            case in_array($key, self::$parseDict['string']):
                $result['fieldType'] = $key;
                if(!isset($result['type'])){
                    $result['type'] = 'string';
                }
                $result['length'] = intval($value);
                break;
            case in_array($key, self::$parseDict['serial']):
                $result['fieldType'] = 'int'.str_replace('serial', '', $key);
                $result['type']      = 'int';
                $result['increment'] = true;
                break;
            case in_array($key, self::$parseDict['int']):
                $result['fieldType'] = $key;
                $result['type']      = 'int';
                break;
            case in_array($key, self::$parseDict['float']):
                $result['fieldType'] = $key;
                $result['type']      = 'float';
                break;
            case in_array($key, self::$parseDict['boolean']):
                $result['type'] = 'bool';
                break;
            case in_array($key, self::$parseDict['object']):
                $result['type'] = $key;
                break;
            case in_array($key, self::$parseDict['value']):
                if($key === 'field'){
                    self::$fieldMap[$value] = $name;
                }
                if($key === 'filter' && !method_exists(get_called_class(), $value)){
                    self::error('filterNotExists', "配置表中 filter 方法[{$value}]不存在，请检查");
                }
                if($key === 'stuffer' && !method_exists(get_called_class(), $value)){
                    self::error('stufferNotExists', "配置表中 stuffer 方法[{$value}]不存在，请检查");
                }
                $result[$key] = $value;
                break;
            default:
                self::error('tableColumnsRulerError', "配置表中 [{$name}] 项值 [{$key}] 有误，请检查");
        }

        return $result;
    }

    /**
     * 提交所有事务
     */
    public static function commitAll()
    {
        foreach(Connector::getPdoList() as $pdo){
            self::restartTransaction($pdo);
        }
    }

    /**
     * 提交指定连接的事务
     *
     * @param \PDO $pdo
     */
    private static function commitTransaction(\PDO $pdo)
    {
        if(self::$debugMode){
            (new Action())->header('_db_debug_mode', true);
            $pdo->rollBack();
        }else{
            $pdo->commit();
        }
    }

    /**
     * @param \PDO $pdo
     */
    private static function restartTransaction($pdo)
    {
        if(!$pdo){
            return;
        }
        if($pdo->inTransaction()){
            self::commitTransaction($pdo);
        }
        if(!$pdo->inTransaction()){
            $pdo->beginTransaction();
        }
    }

    /**
     * 关闭事务
     *
     * @param bool $commitQueue 是否提交之前未提交的事务,注意！！！：如不提交则会清空之前未提交的事务
     * @param bool $restart 是否本次执行结束后又自动开启事务
     * @return $this
     */
    public function closeTransaction($commitQueue, $restart = true)
    {
        $this->connectDb();
        /* @var $pdo \PDO */
        if($this->pdo && $this->pdo->inTransaction()){
            if($commitQueue){
                self::commitTransaction($this->pdo);
            }else{
                $this->pdo->rollBack();
            }
        }
        $this->restartTransaction = $restart;

        return $this;
    }

    /**
     * 手动开启事务
     */
    public static function beginTransaction()
    {
        foreach(Connector::getPdoList() as $pdo){
            /* @var $pdo \PDO */
            if(!$pdo->inTransaction()){
                $pdo->beginTransaction();
            }
        }
    }

    /**
     * 提交本连接上未提交的事务
     *
     * @return $this
     */
    public function commit()
    {
        if($this->pdo && $this->pdo->inTransaction()){
            self::restartTransaction($this->pdo);
        }

        return $this;
    }

    /**
     * 回滚本连接上未提交的事务
     *
     * @return $this
     */
    public function rollBack()
    {
        if($this->pdo && $this->pdo->inTransaction()){
            $this->pdo->rollBack();
        }

        return $this;
    }

    /**
     * 回滚事务
     */
    public static function rollBackAll()
    {
        foreach(Connector::getPdoList() as $pdo){
            /* @var $pdo \PDO */
            if($pdo->inTransaction()){
                $pdo->rollBack();
            }
        }
    }

    /**
     * 与数据库建立连接
     */
    private function connectDb()
    {
        if($this->pdo === null){
            $this->pdo = $this->connector->open();
        }
    }

    /**
     * 预处理SQL
     *
     * @param string $preSql
     *
     * @return \PDOStatement
     */
    private function prepare($preSql)
    {
        $sth                     = $this->pdo->prepare($preSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $this->currentFetchStyle = $this->sqlCollect['fetch_style']??$this->fetch_style;
        $sth->setFetchMode($this->currentFetchStyle);

        return $sth;
    }

    /**
     * 修正错误
     *
     * @param \PDOException $e
     * @param string        $sql
     *
     * @throws Exception
     */

    private function processError(\PDOException $e, $sql)
    {
        switch($e->getCode()){
            /** @noinspection PhpMissingBreakStatementInspection */
            case '7': //数据库不存在
                $creator = new Schema(PROJECT_ROOT, $this->conn);
                $creator->createDataBase();
            case '42P01': //表不存在
                $creator = new Schema(PROJECT_ROOT, $this->conn);
                $creator->createTable($this->quotesTable, static::$columns);
                //标记为该请求可以重试
                Controller::current()->needRetry();

                return;
        }

        $msg = ErrorCode::getMessage($this, $e, $sql);
        self::error('DbOperateError', $msg);

    }

    /**
     * 替换Sql中的占位符
     *
     * @param       $sql
     * @param array $data
     * @return mixed
     */
    private function applyExecutableToPreSql($sql, $data)
    {
        foreach($data as $name => $value){
            $value = is_string($value)? "'".$value."'": $value;
            $sql   = str_replace(':'.$name, $value, $sql);
        }
        $sql = preg_replace('/\([(?:\?\, )|\?]+\)/', '(\''.implode('\', \'', $data).'\')', $sql);

        return $sql;
    }

    /**
     * 执行SQL
     *
     * @param $preSql
     * @param $data
     *
     * @return \PDOStatement
     */
    private function query($preSql, array $data)
    {
        self::$queryTimes++;
        try{
            $this->connectDb();
            $sth = $this->prepare($preSql);
            $sth->execute($data);
        }catch(\PDOException $e){
            $this->rollBack();
            $this->processError($e, $this->applyExecutableToPreSql($preSql, $data));
            $sth = $this->query($preSql, $data);
        }
        //TODO: $this->logs[] = $sth->queryString; //写入文件
        $this->preSql = '';

        if(RUN_MODE != 'production' && self::$debugSql){
            $executeSql = $this->applyExecutableToPreSql($preSql, $data);
            Debug::collect('sql', $executeSql);
        }
        if($this->isCleanSqlCollect){
            $this->cleanSqlCollect();
        }

        if($this->restartTransaction && $this->pdo && !$this->pdo->inTransaction()){
            $this->pdo->beginTransaction();
            $this->restartTransaction = false;
        }

        return $sth;
    }

    /**
     * 清除之前操作搜集的参数
     */
    private function cleanSqlCollect()
    {
        $this->sqlCollect   = []; //一旦查询完成，清理掉上次用过的数据
        $this->data         = [];
        $this->errMsgList   = [];
        $this->connectByOr  = false;
        $this->notCondition = false;
    }

    /**
     * 整理查询结果
     *
     * @param array $list
     * @param array $stack
     * @return array
     */
    private function combQueryResult(array &$list, array $stack)
    {
        if(empty($stack) || empty($list)){
            return $list;
        }

        foreach($list as &$item){
            foreach($stack as $name => $process){
                foreach($process as $p){
                    $item[$name] = $p($item[$name]);
                }
            }
        }

        return $list;
    }

    private function genCombStack()
    {
        $stack = [];
        foreach($this->queryNames as $name){
            $column = static::$columns[$name];

            if(isset($column['type']) !== 'string'){
                $type           = $column['type'];
                $stack[$name][] = function($value) use ($type){
                    return $this->outType($value, $type);
                };
            }
            if(isset($column['filter'])){
                $m              = $column['filter'];
                $stack[$name][] = function($value) use ($m){
                    return $this->$m($value);
                };
            }
        }
        if($this->currentFetchStyle === \PDO::FETCH_NUM){
            $indexStack = [];
            foreach($stack as $name => $item){
                $indexStack[array_search($name, $this->queryNames)] = $item;
            }
            $stack = $indexStack;
        }
        $this->queryNames = [];

        return $stack;
    }

    /**
     * 执行查询
     *
     * @param string $preSql pdo语句
     * @param array  $data
     *
     * @return array
     */
    public function select($preSql, array $data = [])
    {
        $sth  = $this->query('SELECT '.$preSql, $data);
        $list = $sth->fetchAll();

        return $this->combQueryResult($list, $this->genCombStack());
    }

    /**
     * 执行插入
     *
     * @param $preSql
     * @param $data
     *
     * @return array 包含新插入记录的ID
     */
    public function insert($preSql, array $data = [])
    {
        $this->query('INSERT INTO '.$preSql, $data);
        if($this->insertId === null && static::$primaryType === 'increment'){
            $this->insertId = $this->pdo->lastInsertId($this->table.'_'.static::$primary.'_seq');
        }

        return ['insertId' => $this->insertId];
    }

    /**
     * 执行更新
     *
     * @param $preSql
     * @param $data
     *
     * @return array 更新了的行数
     */
    public function update($preSql, array $data = [])
    {
        $sth = $this->query('UPDATE '.$preSql, $data);

        return ['count' => $sth->rowCount()];
    }

    /**
     * 执行删除
     *
     * @param $preSql
     * @param $data
     *
     * @return array
     */
    public function delete($preSql, array $data = [])
    {
        $sth = $this->query('DELETE '.$preSql, $data);

        return ['count' => $sth->rowCount()];
    }

    /**
     * 获取查询次数
     *
     * @return int
     */
    public static function getQueryTimes()
    {
        return self::$queryTimes;
    }

    /*获取查询结果********************/
    /**
     * 打开一个数据模型
     *
     * @return $this
     */
    public static function open()
    {
        return new static();
    }

    /**
     * 将某一组数据反序列化并排除重复值
     *
     * @param $type
     * @return array
     */
    private function mergeSerial($type)
    {
        if(!isset($this->sqlCollect[$type])){
            return [];
        }
        $list = [];
        foreach($this->sqlCollect[$type] as $serial){
            $list = array_merge($list, \preg_split('/ *, */', $serial));
        }

        return array_unique($list);
    }

    /**
     * 解析要使用的字段
     */
    private function collectNames()
    {
        if(!isset($this->sqlCollect['names'])){
            $this->sqlCollect['names'] = ['*'];
        }
        $collectedNames = [];

        $showHiddenList = $this->mergeSerial('showHidden');
        $fieldNameList  = $this->mergeSerial('names');
        $excludeFields  = $this->collectExclude();

        if(in_array('**', $fieldNameList)){//强制要求列出所有,包含隐藏字段
            foreach(static::$columns as $name => $v){
                $collectedNames[] = $name;
            }
        }else{
            foreach($fieldNameList as $n){
                if($n === '*'){ //如果需要映射到字段，需要在sql中使用as,则不能直接使用"*"
                    foreach(static::$columns as $name => $v){
                        if(isset($v['hidden']) && (!in_array($name, $showHiddenList) && !in_array('*', $showHiddenList))){
                            continue;
                        }
                        $collectedNames[] = $name;
                    }
                }else{
                    $collectedNames[] = $n;
                }
            }
        }

        $collectedNames = array_unique($collectedNames);
        foreach($excludeFields as $name){
            $index = array_search($name, $collectedNames);
            unset($collectedNames[$index]);
        }
        //if (count($collectedNames) === count(static::$columns)) {
        //    //$collectedNames = ['*'];
        //}

        $this->queryNames = $collectedNames;
    }

    /**
     * 生成用于查询用的字段列表
     */
    private function genSelectNames()
    {
        $this->collectNames();
        $names  = $this->queryNames;
        $quotes = static::$quotes;
        foreach($names as &$name){
            $field = $name;
            $out   = null;

            if(strpos($name, ' AS ')){
                list($name, $out) = preg_split('/ +AS +/', 2);
            }

            if(isset(static::$columns[$name]['field'])){
                $field = static::$columns[$name]['field'];
                $out   = $out?: $name;
            }

            if($out){
                $name = $quotes.$field.$quotes.' AS '.$quotes.$out.$quotes;
            }elseif($name !== '*'){
                $name = $quotes.$name.$quotes;
            }
        }

        if($this->needFullFieldName){
            foreach($names as &$name){
                $name = $this->quotesTable.'.'.$name;
            }
        }

        $this->preSql = ' '.implode(', ', $names);
    }

    /**
     * 检查写入的数据
     *
     * @param $value
     * @param $columns
     * @param $name
     *
     * @return mixed
     */
    private function checkData($value, $columns, $name)
    {
        if(isset($column['validate'])){
            if(!Validate::check($value, $column['validate'])){
                Error::fatal('DataFormatError', '数据项 ['.$name.'] 格式不正确，期望是一个 ['.$column['validate'].'],当前给的值为: ['.$value.']');
            }
            if($column['validate'] === 'mobile'){
                $value = str_replace('-', '', $value);
            }
        }
        if($columns['type'] === 'array'){
            if(is_array($value)){
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }else{
                Error::fatal('DataFormatError', '数据项 ['.$name.'] 期望是一个 [数组],当前给的值为: ['.var_export($value, true).']');
            }
        }

        $value = $this->inType($value, $columns['type']);

        return $value;
    }

    /**
     * 转换输入值类型
     *
     * @param $value
     * @param $type
     *
     * @return mixed
     */
    private function inType($value, $type)
    {
        switch($type){
            case 'timestamp':
                if(is_numeric($value)){
                    $date = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date->format('Y-m-d\TH:i:s.uP');
                }

                return $value;
            case 'date':
                if(is_numeric($value)){
                    $date = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date->format('Y-m-d');
                }

                return $value;
            case 'bool':
                return $value? 1: 0;
        }

        return $value;
    }

    /**
     * 转换输出值类型
     *
     * @param $value
     * @param $type
     *
     * @return mixed
     */
    private function outType($value, $type)
    {
        switch($type){
            case 'timestamp':
                return strtotime($value);
            case 'bool':
                return (bool)$value;
            case 'float':
                return floatval($value);
            case 'int':
                return intval($value);
            case 'array':
                return json_decode($value, true)?: [];
            default:
                return $value;
        }
    }

    /**
     * 生成uuid
     *
     * @return string
     */
    private static function genUuid()
    {
        $partLength = [8, 4, 4, 4, 12];
        $default    = [];

        foreach($partLength as $i => $length){
            if(isset($default[$i])){
                $default[$i] = str_pad(substr($default[$i], 0, $length), $length, '0', STR_PAD_LEFT);
            }else{
                $default[$i] = '';
                while(strlen($default[$i]) < $length){
                    $default[$i] .= str_pad(base_convert(mt_rand(0, 65535), 10, 16), 4, '0', STR_PAD_LEFT);
                }
            }
        }
        ksort($default);

        return implode('-', $default);
    }

    /**
     * 补充空数据
     *
     * @param $column
     * @return int|mixed|string
     */
    private function genDefaultValue($column)
    {
        if(isset($column['default'])){
            return $column['default'];
        }

        if(isset($column['stuffer'])){
            $stuffer = $column['stuffer'];

            return $this->$stuffer();
        }

        if(isset($column['at'])){
            return microtime(true);
        }
        if($column['type'] === 'uuid'){
            return isset($column['primary'])? self::genUuid(): self::UUID_ZERO;
        }
        if($column['type'] === 'array'){
            return [];
        }

        $type = isset($column['type'])? $column['type']: '';
        switch($type){
            case 'float':
            case 'int':
            case 'bool':
            case 'bit':
            case 'varbit':
            case 'timestamp':
                return 0;
            default:
                return '';
        }
    }

    /**
     * 生成插入记录用的属性列表
     */
    private function genInsertNames()
    {
        $data = $this->sqlCollect['data'];
        if(count($data) === 0){
            return;
        }
        $fields         = [];
        $this->insertId = null;
        foreach(static::$columns as $name => $column){
            //将属性名与字段名进行映射
            $field = $name;
            if(isset($column['field'])){
                $fields[] = $column['field'];
            }else{
                $fields[] = $field;
            }

            if(isset($column['increment'])){//自增类型
                if(!empty($data[$field])){
                    self::error('IncrementFieldNotAllowAssignValue', '自增类型字段不允许给值');
                }
                array_pop($fields);
                continue;
            }
            if(empty($data[$field])){
                if(isset($column['require'])){
                    self::error('InsertToDataBaseRequire', "表 [{$this->table}] 此项 [{$name}] 不允许为空");
                }
                $data[$field] = $this->genDefaultValue($column);
            }
            $this->data[] = $this->checkData($data[$field], $column, $name);
            if(isset($column['primary'])){
                $this->insertId = $data[$field];
            }
        }
        //TODO: 记录丢弃的数据
        //TODO: 数据完整性检查
        //TODO: 验证数据
        $placeholder  = '?';
        $placeholder  .= str_repeat(', ?', count($this->data) - 1);
        $quotes       = static::$quotes;
        $this->preSql .= ' ('.$quotes.implode("{$quotes}, {$quotes}", $fields).$quotes;
        $this->preSql .= ') VALUES ('.$placeholder.')';
    }

    /**
     * 预处理WHERE语句,为后期生成WHERE语句做准备
     *
     * @return array
     */
    private function preParseWhere()
    {
        $bracketLevel = 0;
        $symbol       = [];
        $index        = 0;
        foreach($this->sqlCollect['where'] as $index => &$where){ //通观全局
            $symbol[$index]    = ['', '', ' '];
            $symbol[$index][1] = $where[2]? 'OR ': 'AND ';
            if($where[3] & self::BOUND_BEGIN){ //括号不关闭 为前一个加括号，如当前为第一个，则为自身加括号
                $symbol[$index][0] = '(';
                $bracketLevel++;
            }
            if($where[3] & self::BOUND_END && $bracketLevel > 0){ //括号关闭 当前括号未关闭时关闭
                $symbol[$index][2] = ') ';
                $bracketLevel--;
            }
            if($where[3] & self::BOUND_OTHER && $index > 0){ //需要将自身独立出来，将前面where用括号括起来
                $symbol[0][0] .= '(';
                for(; $bracketLevel > 0; $bracketLevel--){//将前面未关闭的括号全关闭
                    $symbol[$index - 1][2] .= ')';
                }
                $symbol[$index - 1][2] .= ') ';
                if(!empty($this->sqlCollect['where'][$index + 1])){
                    $this->sqlCollect['where'][$index + 1][3] = self::BOUND_BEGIN;
                }
            }
            if($where[4]){
                $where[0] = $this->matchFieldForWhere($where[0]);
            }
        }

        if($symbol){
            $symbol[0][1] = '';
        }
        for(; $bracketLevel > 0; $bracketLevel--){
            $symbol[$index][2] .= ')';
        }

        return $symbol;
    }

    /**
     * 通过名字寻找字段的配置信息
     *
     * @param $name
     * @return array|null
     */
    private function findColumnByName($name)
    {
        if(isset(static::$columns[$name])){ //找到了属性
            $column = static::$columns[$name];
        }else{
            if(isset(static::$fieldMap[$name])){
                $column = static::$columns[static::$fieldMap[$name]];
                //TODO 警告不应在此处使用字段名
            }else{
                $column = null;
            }
        }

        return $column;
    }

    /**
     * 搜集整理需要忽略的字段列表
     *
     * @return array
     */
    private function collectExclude()
    {
        $list = [];
        if(isset($this->sqlCollect['exclude'])){
            foreach($this->sqlCollect['exclude'] as $exclude){
                $list = \array_merge($list, \preg_split('/ *, */', $exclude));
            }
        }

        return $list;
    }

    /**
     * 自动插入更新记录的时间
     *
     * @param $data
     */
    private function genUpdateTime(&$data)
    {
        if(isset($this->sqlCollect['ignoreUpdateTime']) && $this->sqlCollect['ignoreUpdateTime']){
            return;
        }

        foreach(static::$columns as $name => $column){
            if(isset($column['at']) && $column['at'] === 'update'){
                if(!isset($data[$name])){
                    $data[$name] = \microtime(true);
                }
            }
        }
    }

    /**
     * 生成插入记录用的属性列表
     */
    private function genUpdateNames()
    {
        $data = $this->sqlCollect['data'];
        if(count($data) === 0){
            return;
        }
        $fields = [];

        $this->genUpdateTime($data);

        $fieldValues   = [];
        $excludeFields = $this->collectExclude();
        $fieldNameList = $this->mergeSerial('names');
        if(empty($fieldNameList) || in_array('*', $fieldNameList) || in_array('**', $fieldNameList)){
            $fieldNameList = null;
        }

        foreach($excludeFields as $name){
            if(isset($data[$name])){
                unset($data[$name]);
            }
        }

        foreach($data as $name => $value){
            if($value === null){
                continue;
            }
            if($fieldNameList && !in_array($name, $fieldNameList)){
                continue;
            }
            //将属性名与字段名进行映射
            $column = $this->findColumnByName($name);
            if($column === null || isset($column['primary'])){//不允许更新主键的内容
                continue;
            }
            $field    = $column['field']??$name;
            $quotes   = static::$quotes;
            $fields[] = $quotes.$field.$quotes;
            if(is_string($value) && substr($value, 0, 1) === '`' && substr($value, -1, 1) === '`'){//值为可执行代码
                $value = trim($value, '` ');
                if(in_array($column['type'], ['int', 'float']) && preg_match('/^=([\+\-]) *(\d) *$/', $value, $match)){
                    $fieldValues[] = "{$quotes}$field{$quotes} {$match[1]} {$match[2]}";
                }else{
                    preg_match_all('/(\\\\*)\:(\w+[a-z0-9_\-]*)/i', $value, $matches, PREG_SET_ORDER);//处理转义符
                    foreach($matches as $match){
                        if($match[1]){
                            if(strlen($match[1]) % 2 === 1){
                                $value = str_replace($match[0], substr($match[1], 0, intval(strlen($match[1]) / 2)).':'.$match[2], $value);
                                continue;
                            }else{
                                $match[1] = substr($match[1], 0, strlen($match[1]) / 2);
                            }
                        }
                        $field = $this->nameMapField($match[2]);
                        $value = $match[1].$field;
                    }
                    $fieldValues[] = $value;
                }
            }else{
                if(is_string($value) && substr($value, 0, 2) === '\`'){
                    $value = substr($value, 1);
                }
                $fieldValues[]           = ':u_'.$field;
                $this->data['u_'.$field] = $this->checkData($value, $column, $name);
            }
        }
        //TODO: 记录丢弃的数据
        //TODO: 数据完整性检查
        //TODO: 验证数据
        $buffer = [];
        foreach($fields as $index => $f){
            $buffer[] = "{$f} = {$fieldValues[$index]}";
        }
        $this->preSql .= ' SET '.implode(', ', $buffer);
    }

    /**
     * 匹配处理条件语句中的字段名
     *
     * @param string $sql
     *
     * @return string
     */
    private function matchFieldForWhere($sql)
    {
        $conditions = preg_split('/( +and +| +or +)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        for($i = 0, $l = count($conditions); $i < $l; $i += 2){
            $conditions[$i] = preg_replace_callback('/^([\( ]*)([\w_]+)(.*?)(:?[\w_,: ]+)([\) ]*)$/', function($match){
                $bracketStart = str_replace(' ', '', $match[1]);
                $name         = $match[2];
                $sign         = strtoupper(trim($match[3]));
                $value        = $match[4];
                $bracketEnd   = str_replace(' ', '', $match[5]);

                $fullField = $this->nameMapField($name);
                if(isset(static::$columns[$name]['lower'])){
                    $fullField = "lower({$fullField})";
                    $value     = "lower({$value})";
                }

                return "{$bracketStart}{$fullField} {$sign} {$value}{$bracketEnd}";
            }, $conditions[$i]);
            if($l > $i + 1){
                $conditions[$i + 1] = trim($conditions[$i + 1]);
            }
        }

        return implode(' ', $conditions);
    }

    /**
     * 生成条件语句
     *
     * @return string
     */
    private function genWhere()
    {
        if(empty($this->sqlCollect['where'])){
            return '';
        }
        $symbol = $this->preParseWhere();

        $collectedData = [];
        $whereSql      = '';
        foreach($this->sqlCollect['where'] as $index => $where){
            $sql = $where[0];
            foreach($where[1] as $k => $v){
                $sql = str_replace(":{$k}", ":w_{$index}_{$k}", $sql);

                $collectedData["w_{$index}_{$k}"] = $v;
            }
            $whereSql .= $symbol[$index][1].$symbol[$index][0].$sql.$symbol[$index][2];
        }

        if(isset($this->sqlCollect['subCondition'])){
            foreach($this->sqlCollect['subCondition'] as $id => $condition){
                $whereSql = str_replace($id, $condition, $whereSql);
            }
        }
        $whereSql     = ' WHERE '.$whereSql;
        $this->preSql .= $whereSql;
        $this->data   = array_merge($this->data, $collectedData);

        return $whereSql;
    }

    /**
     * 将属性换回字段
     *
     * @param $name
     *
     * @return string
     */
    private function nameMapField($name)
    {
        if(isset(static::$columns[$name]['field'])){
            $name = static::$columns[$name]['field'];
        }
        $name = static::$quotes.$name.static::$quotes;
        if($this->needFullFieldName){
            $name = $this->quotesTable.'.'.$name;
        }

        return $name;
    }

    /**
     * 处理字段名
     *
     * @param $name
     * @return string
     */
    private function quotesFiledName($name)
    {
        if(strpos($name, '.')){
            $ns     = explode('.', $name);
            $quotes = static::$quotes;

            return $quotes.implode($quotes.'.'.$quotes, $ns).$quotes;
        }

        return $name;
    }

    /**
     * 将逗号隔开的属性列表换回字段列表
     *
     * @param $names
     * @return string
     */
    private function namesMapField($names)
    {
        $list = explode(',', $names);
        $buf  = [];
        foreach($list as $name){
            $buf[] = $this->nameMapField(trim($name));
        }

        return implode(', ', $buf);
    }

    /**
     * 生成GROUP
     *
     * @return string
     */
    private function genGroup()
    {
        if(!isset($this->sqlCollect['group'])){
            return '';
        }
        $groupSql = '';
        foreach($this->sqlCollect['group'] as $field){
            $groupSql .= ' GROUP BY '.$this->namesMapField($field);
        }
        $this->preSql .= $groupSql;

        return $groupSql;
    }

    /**
     * 生成排序规则
     */
    private function genOrder()
    {
        if(!isset($this->sqlCollect['order'])){
            return;
        }
        $sqlBuffer = [];
        foreach($this->sqlCollect['order'] as $oa){
            $sqlBuffer[] = $this->nameMapField($oa[0]).' '.strtoupper($oa[1]);
        }

        if($sqlBuffer){
            $this->preSql .= ' ORDER BY '.implode(', ', $sqlBuffer);
        }
    }

    /**
     * 由于PGSQL UPDATE不支持LIMIT语句，在此替代实现
     */
    private function applyLimitForEdit()
    {
        if(isset($this->sqlCollect['limit']) && $this->sqlCollect['limit'][0]){
            $model             = new static();
            $model->sqlCollect = $this->sqlCollect;
            $model->field(static::$primary, true);

            $this->in(static::$primary, $model);
        }
    }

    /**
     * 生成分页
     */
    private function genLimit()
    {
        if(isset($this->sqlCollect['limit'])){
            $length       = $this->sqlCollect['limit'][0];
            $this->preSql .= " LIMIT {$length}";

            if($this->sqlCollect['limit'][1] >= 2){
                $offset       = ($this->sqlCollect['limit'][1] - 1) * $length;
                $this->preSql .= " OFFSET {$offset}";
            }
        }
    }

    /**
     * 生成锁定语句
     */
    private function genLock()
    {
        if(isset($this->sqlCollect['lock'])){
            $this->preSql .= ' FOR UPDATE';
            if($this->sqlCollect['lockSkip']){
                $this->preSql .= ' SKIP LOCKED';
            }
        }
    }

    /**
     * 应用删除标记
     */
    private function applyTrashed()
    {
        $sign = 'hidden';
        if(isset($this->sqlCollect['trashed'])){ //标记为删除的也列出
            $sign = $this->sqlCollect['trashed'];
        }
        if($sign === 'with'){
            return;
        }
        foreach(static::$columns as $name => $column){
            if(isset($column['del'])){
                if($sign === 'only'){
                    $this->where("$name=true", [], self::BOUND_OTHER);
                }else{
                    $this->where("$name=false", [], self::BOUND_OTHER);
                }
                break;
            }
        }
    }

    /**
     * 生成查询语句
     *
     * @return string
     */
    private function getSelectSql()
    {
        $this->applyTrashed();
        $this->genSelectNames();
        $this->preSql .= ' FROM '.$this->quotesTable;
        $this->genWhere();
        $this->genGroup();
        $this->genOrder();
        $this->genLimit();

        return $this->preSql;
    }

    /**
     * 根据主键取出一条记录
     *
     * @param string $primary 主键值
     * @param string $names 要取出的字段表
     *
     * @return array
     */
    public function get($primary, $names = '*')
    {
        $this->where(static::$primary.'=:key', ['key' => $primary]);

        return $this->first($names);
    }

    /**
     * 获取指定记录的指定值
     *
     * @param $primary
     * @param $name
     * @return mixed
     */
    public function getValue($primary, $name)
    {
        $res = $this->get($primary, $name);

        return isset($res[$name])? $res[$name]: null;
    }

    /**
     * 直接获取指定的属性的值
     *
     * @param $name
     * @return mixed|null
     */
    public function value($name)
    {
        $res = $this->first($name);

        return isset($res[$name])? $res[$name]: null;
    }

    /**
     * 返回数字索引数组
     *
     * @return $this
     */
    public function index()
    {
        $this->sqlCollect['fetch_style'] = \PDO::FETCH_NUM;

        return $this;
    }

    /**
     * 取出符合条件的第一条记录
     *
     * @param string $names 要取出的字段表
     *
     * @return array
     */
    public function first($names = '*')
    {
        $this->limit(1);

        $res = $this->fetch($names);
        if($res){
            return $res[0];
        }

        return $res;
    }

    /**
     * 取出指定条数
     *
     * @param int    $length
     * @param string $names 要列出的字段
     *
     * TODO: 保持Where条件的顺序，以达到能让开发人员优化查询条件的目的
     *
     * @return array
     */
    public function take($length, $names = '*')
    {
        $this->limit($length);

        return $this->fetch($names);
    }

    /**
     * 生成Select语句
     */
    private function parseSelectSql()
    {
        $this->applyTrashed();
        $this->genSelectNames();
        $this->preSql .= ' FROM '.$this->quotesTable;
        $whereSql     = $this->genWhere();
        $groupSql     = $this->genGroup();
        $this->genOrder();
        $this->genLimit();
        $this->genLock();

        $this->lastPageInfo = null;

        if(isset($this->sqlCollect['needTotal']) && $this->sqlCollect['needTotal'] && isset($this->sqlCollect['limit'])){
            $pageSize  = $this->sqlCollect['limit'][0];
            $pageIndex = $this->sqlCollect['limit'][1];

            $this->lastPageInfo = [
                -1,
                $pageIndex,
                $pageSize,
                'COUNT(*) FROM '.$this->quotesTable.$whereSql.$groupSql,
                $this->data
            ];
        }
    }

    /**
     * 取出查询结果
     *
     * @param string $field 要获取的属性列表
     *
     * @return array
     */
    public function fetch($field = '*')
    {
        $this->field($field);
        $this->parseSelectSql();

        $list = $this->select($this->preSql, $this->data);

        return $list;
    }

    /**
     * 以非索引数组返回查询结果
     *
     * @param string $field 要获取的属性列表
     *
     * @return array
     */
    public function fetchIndex($field = '*')
    {
        $this->index();

        return $this->fetch($field);
    }

    /**
     * 获取结果，连同分页信息
     *
     * @param string $field
     * @return array
     */
    public function fetchWithPage($field = '*')
    {
        if(!isset($this->sqlCollect['limit'])){
            self::error('notCallSetPage', '未调用setPage方法');
        }
        $data = $this->fetch($field);
        $page = $this->getLastPageInfo();

        return [
            'list'  => $data,
            'total' => $page[0],
            'page'  => $page[1],
            'size'  => $page[2]
        ];
    }

    /**
     * 获取分页信息
     * 返回值为有三个元素的数组，依次为 total,page,size
     *
     * @return array
     */
    public function getLastPageInfo()
    {
        if($this->lastPageInfo){
            if($this->lastPageInfo[0] === -1){
                $this->lastPageInfo[0] = $this->select($this->lastPageInfo[3], $this->lastPageInfo[4])[0]['count'];
                unset($this->lastPageInfo[3]);
                unset($this->lastPageInfo[4]);
            }

            return $this->lastPageInfo;
        }else{
            return [0, 0, 0];
        }
    }

    /**
     * 要操作的字段
     *
     * @param string $field
     * @param bool   $clean
     *
     * @return $this
     */
    public function field($field, $clean = false)
    {
        if($clean){
            $this->sqlCollect['names'] = [];
        }
        if($field){
            $this->sqlCollect['names'][] = $field;
        }

        return $this;
    }

    /**
     * 将查询结果用叠代器的形式返回
     *
     * @param string $field 获取的字段列表
     *
     * @return \Generator
     */
    public function fetchIterate($field)
    {
        $this->field($field);
        $this->parseSelectSql();

        $sth   = $this->query('SELECT '.$this->preSql, $this->data);
        $stack = $this->genCombStack();

        while($item = $sth->fetch()){
            $list = [$item];
            yield $this->combQueryResult($list, $stack)[0];
        }
    }

    /**
     * 添加记录
     *
     * @param array $data
     *
     * @return array 包含新增记录的ID
     */
    public function add(array $data = [])
    {
        $this->pushData($data);
        $this->preSql = $this->quotesTable;
        $this->genInsertNames();

        return $this->insert($this->preSql, $this->data);
    }

    /**
     * 批量添加记录 需传递一个二维数组
     *
     * @param array $data
     *
     * @return array 执行结果 包含新增记录的ID列表
     */
    public function addMass(array $data)
    {
        $insertIds = [];
        foreach($data as $d){
            $insertIds[] = $this->add($d)['insertId'];
        }

        return ['insertIdList' => $insertIds];
    }

    /**
     * 编辑
     *
     * @param array $data
     *
     * @return array
     */
    public function edit(array $data = [])
    {
        $this->applyLimitForEdit();
        $this->pushData($data);
        $this->preSql = $this->quotesTable;
        $this->genUpdateNames();
        $this->genWhere();

        return $this->update($this->preSql, $this->data);
    }

    /**
     * 设置修改指定属性的值
     *
     * @param $name
     * @param $value
     * @return array
     */
    public function set($name, $value)
    {
        return $this->edit([$name => $value]);
    }

    /**
     * 存入数据，供后续使用
     *
     * @param array $data
     * @param bool  $recursive
     * @param bool  $overwrite
     * @return $this
     */
    public function pushData(array $data, $recursive = false, $overwrite = true)
    {
        if(empty($this->sqlCollect['data'])){
            $this->sqlCollect['data'] = $data;

            return $this;
        }
        if($recursive){
            if($overwrite){
                $this->sqlCollect['data'] = array_replace_recursive($this->sqlCollect['data'], $data);
            }else{
                $this->sqlCollect['data'] = array_replace_recursive($data, $this->sqlCollect['data']);
            }
        }else{
            if($overwrite){
                $this->sqlCollect['data'] = array_merge($this->sqlCollect['data'], $data);
            }else{
                $this->sqlCollect['data'] = array_merge($data, $this->sqlCollect['data']);
            }
        }

        return $this;
    }

    /**
     * 在数据中存入值，供后续使用
     *
     * @param      $name
     * @param      $value
     * @param bool $overwrite
     * @return $this
     */
    public function setData($name, $value, $overwrite = true)
    {
        return $this->pushData([$name => $value], false, $overwrite);
    }

    /**
     * 某一字段值增长
     *
     * @param     $name
     * @param int $value
     * @return $this
     */
    public function increment($name, $value = 1)
    {
        $symbol = '+';
        if($value < 0){
            $symbol = '-';
            $value  = abs($value);
        }
        $field = $this->nameMapField($name);
        if($field){
            $this->pushData([$name => "`{$field}{$symbol}{$value}`"]);
        }

        return $this;
    }

    /**
     * 如果存在则编辑，否则插入，通过判断返回的内容来判断执行的方式
     *
     * @param array  $data
     * @param string $name 以该属性的内容判定是否冲突,默认为主键
     *
     * @return array
     */
    public function replace(array $data, $name = null)
    {
        if($name === null){
            $name = static::$primary;
        }
        if(!empty($data[$name])){
            $row = $this->equals($name, $data[$name])->first($name);
            if($row){
                $value = $data[$name];
                unset($data[$name]);

                return $this->equals($name, $value)->edit($data);
            }
        }

        return $this->add($data);
    }

    /**
     * 删除记录（软删除）
     *
     * @return array 删除的数量
     */
    public function remove()
    {
        $data = [];
        foreach(static::$columns as $name => $column){
            if(isset($column['del'])){
                $data[$name] = true;
                $this->where("{$name}=false", [], self::BOUND_OTHER);
            }
        }
        if(!count($data)){
            Error::fatal('notDefinedDelField', "表 [{$this->table}] 未定义逻辑删除字段,请检查");
        }

        return $this->edit($data);
    }

    /**
     * 还原删除的数据
     *
     * @return array 还原的条数
     */
    public function restore()
    {
        $data = [];
        foreach(static::$columns as $name => $column){
            if(isset($column['del'])){
                $data[$name] = false;
                $this->where("{$name}=true", [], self::BOUND_OTHER);
            }
        }
        if(!count($data)){
            Error::fatal('notDefinedDelField', "表 [{$this->table}] 未定义逻辑删除字段,请检查");
        }

        return $this->edit($data);
    }

    /**
     * 物理删除记录
     *
     * @param bool $force 配合软删除标记，如果不为真则只删除软标记为删除的记录
     *
     * @return array
     */
    public function destroy($force = false)
    {
        if($force === true){
            $this->hiddenTrashed();
        }else{
            $this->onlyTrashed();
        }
        $this->applyTrashed();
        $this->preSql = ' FROM '.$this->quotesTable;
        $this->genWhere();
        $this->genLimit();

        return $this->delete($this->preSql, $this->data);
    }

    /**
     * 获取总记录条数
     *
     * @return int
     */
    public function count()
    {
        $this->applyTrashed();
        $this->preSql .= ' COUNT(*)';
        $this->preSql .= ' FROM '.$this->quotesTable;
        $this->genWhere();
        $this->genGroup();

        $res = $this->select($this->preSql, $this->data);

        return $res[0]['count'];
    }

    /**
     * 获取某字段求和
     *
     * @return int
     */
    public function sum($field)
    {
        $fieldInfo = static::$columns[$field]??null;
        $field = !empty($fieldInfo['field']) ? $fieldInfo['field'] : $field;
        $this->applyTrashed();
        $this->preSql .= ' SUM('.$field.') as sum';
        $this->preSql .= ' FROM '.$this->quotesTable;
        $this->genWhere();
        $this->genGroup();

        $res = $this->select($this->preSql, $this->data);

        return $res[0]['sum'];
    }

    /**
     * 通过主键查找,以便进行下一步操作
     *
     * @param $primary
     *
     * @return $this
     */
    public function find($primary)
    {
        $this->where(static::$primary.' = :primary', ['primary' => $primary]);

        return $this;
    }

    /**
     * 在操作中需要排除的字段
     *
     * @param $fields
     * @return $this
     */
    public function exclude($fields)
    {
        $this->sqlCollect['exclude'][] = $fields;

        return $this;
    }

    /**
     * 查询条件
     *
     * @param string $sql
     * @param array  $data
     * @param int    $bound 与前后条件的边界化分
     * @param bool   $matchField 是否将语句中的属性名替换为字段名
     *
     * @return $this
     */
    public function where($sql, array $data = [], $bound = self::BOUND_NONE, $matchField = true)
    {
        $this->sqlCollect['where'][] = [$sql, $data, $this->isEitherCondition(), $bound, $matchField];

        return $this;
    }

    private function whereMake($sql, $name, $data)
    {
        if($this->isNotCondition()){
            $sql = strtr($sql, [
                ' >= ' => ' < ',
                ' <= ' => ' > ',
                ' > '  => ' <= ',
                ' < '  => ' >= ',
                ' = '  => ' != '
            ]);
        }

        $column = static::$columns[$name]??null;
        if($column){
            if($column['type'] === 'timestamp' || $column['type'] === 'date'){
                foreach($data as &$v){
                    $v = $this->inType($v, $column['type']);
                }
            }
        }

        $this->where($sql, $data);
    }


    /**
     * 下一个条件与前一个条件间采用OR连接
     *
     * @return $this
     */
    public function either()
    {
        $this->connectByOr = true;

        return $this;
    }

    /**
     * 与前一个条件是否采用OR连接
     *
     * @return bool
     */
    private function isEitherCondition()
    {
        if($this->connectByOr){
            $this->connectByOr = false;

            return true;
        }

        return false;
    }

    /**
     * 为下一个条件增加not前缀
     *
     * @return $this
     */
    public function not()
    {
        $this->notCondition = true;

        return $this;
    }

    /**
     * 是否需要增加not前缀
     *
     * @return bool
     */
    private function isNotCondition()
    {
        if($this->notCondition){
            $this->notCondition = false;

            return true;
        }

        return false;
    }

    /**
     * 相等条件
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function equals($name, $value)
    {
        $this->whereMake("$name = :value", $name, ['value' => $value]);

        return $this;
    }

    /**
     * 字段相等
     *
     * @param $name
     * @param $filed
     * @return $this
     */
    public function fieldEquals($name, $filed)
    {
        $filed = $this->quotesFiledName($filed);
        $name  = $this->nameMapField($name);

        $this->whereMake("$name = {$filed}", $name, []);

        return $this;
    }

    /**
     * 大于某值
     *
     * @param string           $name 要比较的属性名
     * @param int|float|string $value 日期可以是字符串
     * @param bool             $contain 是否包含起点
     * @return $this
     */
    public function more($name, $value, $contain = true)
    {
        $equ = $contain? '=': '';
        $this->whereMake("$name >{$equ} :value", $name, ['value' => $value]);

        return $this;
    }

    /**
     * 小于某值
     *
     * @param string           $name 要比较的属性名
     * @param int|float|string $value 日期可以是字符串
     * @param bool             $contain 是否包含止点
     * @return $this
     */
    public function less($name, $value, $contain = true)
    {
        $equ = $contain? '=': '';
        $this->whereMake("$name <{$equ} :value", $name, ['value' => $value]);

        return $this;
    }

    /**
     * 限定在某范围内
     *
     * @param string           $name 要比较的属性名
     * @param int|float|string $start 日期可以是字符串
     * @param int|float|string $end 日期可以是字符串
     * @param bool             $contain 是否包含起始点
     * @return $this
     */
    public function between($name, $start, $end, $contain = true)
    {
        $equ = $contain? '=': '';
        $this->whereMake("$name >{$equ} :start AND $name <{$equ} :end", $name, ['start' => $start, 'end' => $end]);

        return $this;
    }

    /**
     * 相等条件
     *
     * @param array  $list
     * @param string $link
     * @param int    $bound
     *
     * @return $this
     */
    public function equalsMulti(array $list, $link = 'and', $bound = self::BOUND_NONE)
    {
        $glue   = strtoupper($link);
        $buffer = [];
        if($this->isNotCondition()){
            foreach($list as $name => $value){
                $buffer[] = "$name!=:$name";
            }
        }else{
            foreach($list as $name => $value){
                $buffer[] = "$name=:$name";
            }
        }
        $this->where(implode(" {$glue} ", $buffer), $list, $bound);

        return $this;
    }

    /**
     * 准备模糊匹配所需的参数
     *
     * @param string|array $name 参与搜索的属性
     * @param string       $keyword 搜索的值
     * @param int          $model ‘%’所在的位置
     * @param int          $glue 条件间的连接关系
     * @return array
     **/
    private function preParseLike($name, $keyword, $model, $glue)
    {
        if($model & self::LIKE_LEFT){
            $keyword = '%'.$keyword;
        }
        if($model & self::LIKE_RIGHT){
            $keyword = $keyword.'%';
        }

        $data = ['keywords' => $keyword];

        $sqlBuilder = [];

        if(is_array($name)){
            foreach($name as $n){
                $sqlBuilder[] = "{$n} like :keywords";
            }
        }else{
            $sqlBuilder[] = "{$name} like :keywords";
        }

        $glue = $glue === self::LINK_OR? 'or': 'and';

        $sql = implode(" {$glue} ", $sqlBuilder);

        return [$sql, $data];
    }

    /**
     * 模糊搜索 与其条件用AND连接
     *
     * @param string|array $name 参与搜索的属性
     * @param string       $keyword 搜索的值
     * @param int          $model ‘%’所在的位置
     * @param int          $glue 条件间的连接关系
     * @param int          $bound 与前后条件的边界化分
     *
     * @return $this
     */
    public function like($name, $keyword, $model = self::LIKE_BOTH, $glue = self::LINK_OR, $bound = self::BOUND_NONE)
    {
        list($sql, $data) = $this->preParseLike($name, $keyword, $model, $glue);

        return $this->where($sql, $data, $bound);
    }

    /**
     * 全文本搜索
     *
     * @param array  $condition 搜索相关的字段
     * @param string $keyword 关键字
     * @param int    $glue 条件间的连接方式
     * @param int    $bound 与前后条件的边界化分
     *
     * @return $this
     */
    public function search(array $condition, $keyword, $glue = self::LINK_OR, $bound = self::BOUND_NONE)
    {

        return $this;
    }

    /**
     * 按分页查询
     *
     * @param int  $pageSize
     * @param int  $page
     * @param bool $needTotal 是否输出总数
     *
     * @return $this
     */
    public function page($pageSize, $page = null, $needTotal = true)
    {
        if($pageSize === null){
            if(isset($this->sqlCollect['limit'])){
                $pageSize = $this->sqlCollect['limit'][1];
            }else{
                $pageSize = 1;
            }
        }
        $this->sqlCollect['limit'] = [$pageSize, $page];
        $this->needTotal($needTotal);

        return $this;
    }

    /**
     * 设置分页信息
     *
     * @param array $options
     * @param bool  $needTotal
     * @return \jt\Model
     */
    public function setPage(array $options, $needTotal = true)
    {
        return $this->page($options['pageSize'], $options['page'], $needTotal);
    }

    /**
     * 限制取出的条数
     *
     * @param $length
     *
     * @return $this
     */
    public function limit($length)
    {
        $this->sqlCollect['limit'] = [$length, 0];

        return $this;
    }

    /**
     * 设置是否需要总数
     *
     * @param bool $needTotal
     *
     * @return $this
     */
    public function needTotal($needTotal = true)
    {
        $this->sqlCollect['needTotal'] = $needTotal;

        return $this;
    }

    /**
     * 查询软删除的数据
     *
     * @return $this
     */
    public function withTrashed()
    {
        $this->sqlCollect['trashed'] = 'with';

        return $this;
    }

    /**
     * 只查询软删除的数据
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        $this->sqlCollect['trashed'] = 'only';

        return $this;
    }

    /**
     * 清除该标记，让语句恢复默认行为
     *
     * @return $this
     */
    public function hiddenTrashed()
    {
        $this->sqlCollect['trashed'] = 'hidden';

        return $this;
    }

    /**
     * 显示隐藏字段
     *
     * @param string $field 要查询的字段
     *
     * @return $this
     */
    public function withHidden($field = '*')
    {
        $this->sqlCollect['showHidden'][] = $field;

        return $this;
    }

    /**
     * exists查询
     *
     * @param \jt\Model $model 关联的模型
     * @param int       $bound 与前后条件的边界化分
     *
     * @return $this
     */
    public function exists(Model $model, $bound = self::BOUND_NONE)
    {
        $model->needFullFieldName = true;
        if(empty($model->sqlCollect['names'])){
            $model->field($model::$primary);
        }
        $sql = ($this->isNotCondition()? 'NOT ': '').'EXISTS (SELECT '.$model->getSelectSql().')';
        $this->where($sql, $model->data, $bound, false);

        return $this;
    }

    /**
     * in 查询
     *
     * @param string      $field 要查的字段
     * @param array|Model $list 值列表 可以是索引数组
     * @param int         $bound 与前后条件的边界化分
     *
     * @return $this
     */
    public function in($field, $list, $bound = self::BOUND_NONE)
    {
        if(is_array($list)){
            $keys = [];
            foreach($list as $k => $v){
                $keys[] = ":{$k}";
            }
            $instr   = implode(',', $keys);
            $notSign = $this->isNotCondition()? 'not ': '';

            $sql = "$field {$notSign}in ({$instr})";

            $this->where($sql, $list, $bound);

        }elseif($list instanceof Model){
            $list->field($list::$primary, true);
            $sql = $this->nameMapField($field).' '.($this->isNotCondition()? 'NOT ': '').'IN (SELECT '.$list->getSelectSql().')';
            $this->where($sql, $list->data, $bound, false);
        }

        return $this;
    }

    /**
     * 设置GROUP
     *
     * @param $name
     *
     * @return $this
     */
    public function group($name)
    {
        $this->sqlCollect['group'][] = $name;

        return $this;
    }

    /**
     * 排序
     *
     * @param string $attr 排序的属性(将自动映射为字段)
     * @param string $order asc | desc
     * @param string $model 以指定的模块的属性排序
     *
     * @return $this
     */
    public function order($attr, $order = 'asc', $model = null)
    {
        $fields = preg_split('/ *, */', $attr);
        foreach($fields as $field){
            $this->sqlCollect['order'][] = [$field, $order, $model];
        }

        return $this;
    }

    /**
     * 锁定某行记录
     *
     * @param bool   $skip 是否跳过锁定的行
     * @param string $table 指定锁定的表（当联合查询时才有用）
     * @return $this
     */
    public function lock($skip = false, $table = null)
    {
        $this->sqlCollect['lock']      = true;
        $this->sqlCollect['lockSkip']  = $skip;
        $this->sqlCollect['lockTable'] = $table;

        return $this;
    }

    /**
     * 是否忽略更新时间
     *
     * @param bool $is
     * @return $this
     */
    public function ignoreUpdateTime($is = true)
    {
        $this->sqlCollect['ignoreUpdateTime'] = $is;

        return $this;
    }

    /**
     * 遇到错误时的处理办法
     *
     * @param string $action 处理方式 中止程序:exit,继续:continue
     *
     * @return $this
     */
    public function onError($action)
    {
        $this->errorAction = $action;

        return $this;
    }

    /**
     * 当遇到错误时返回的自定义错误消息
     *
     * @param $list
     * @return $this
     */
    public function errMsg($list)
    {
        $this->errMsgList = $list;

        return $this;
    }

    /**
     * 统一抛出错误
     *
     * @param $code
     * @param $msg
     * @throws Exception
     */
    public static function error($code, $msg)
    {
        $te = new Exception($code.':'.$msg.' Model:['.get_called_class().']');
        $te->setIgnoreTraceLine(3);
        throw $te;
    }

    /**
     * 获取当前所用的数据库类弄
     *
     * @return string
     */
    public function getConnectorType()
    {
        return $this->connector->getDatabaseType();
    }

    /**
     * 获取当前所用错误列表
     *
     * @return array
     */
    public function getErrorMsgList()
    {
        return $this->errMsgList;
    }

    /**
     * 开启调试模式
     *
     * @param bool $commit 是否将结果写入数据库
     * @param bool $printSql 是否打印执行过的SQL
     */
    public static function debug($commit = false, $printSql = true)
    {
        self::$debugMode = !$commit;
        self::$debugSql  = $printSql;
    }

    /**
     * 生成SQL语句
     *
     * @param string $method
     * @return string
     */
    public function genSql($method = 'select')
    {
        $sql = '';
        switch($method){
            case 'select':
                $this->parseSelectSql();
                $sql = 'SELECT '.$this->applyExecutableToPreSql($this->preSql, $this->data);
                break;
        }

        $this->preSql = '';
        if($this->isCleanSqlCollect){
            $this->cleanSqlCollect();
        }

        return $sql;
    }
}