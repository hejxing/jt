<?php

/**
 *
 * Copyright 2015, jentian.com
 */
namespace jt;

define('MODEL_UUID_ZERO', '00000000-0000-0000-0000-000000000000');

use jt\utils\Helper;
use jt\lib\database\Connector;
use jt\lib\database\Schema;
use jt\lib\database\ErrorCode;

class Model
{
    /**
     * 连接名
     *
     * @type string
     */
    protected $conn = 'generic';
    /**
     * @type \PDO
     */
    private $pdo = null;
    /**
     * 当前操作的表
     *
     * @type string
     */
    protected $table = '';
    /**
     * 数据库类型
     *
     * @type Connector
     */
    protected $connector = null;
    /**
     * 表前缀 无需设定，通过配置文件设定
     *
     * @type string
     */
    protected static $prefix = '';
    /**
     * 数据库日志
     *
     * @type array
     */
    protected $logs = [];
    /**
     * 表主键字段名.
     *
     * @var string
     */
    protected static $primary = 'id';
    /**
     * 主键类型
     *
     * @type string
     */
    protected static $primaryType = 'uuid';
    /**
     * 最新插入记录的ID值
     *
     * @type bool
     */
    protected $insertId = null;
    /**
     *
     * @type array
     */
    private $lastPageInfo = null;
    /**
     * 每页条数.
     *
     * @var int
     */
    protected $perPage = 15;
    /**
     * 数据库表结构
     *
     * @type array
     */
    protected static $columns = []; //静态属性，便于缓存解析结果
    /**
     * 字段属性映射表
     *
     * @type array
     */
    protected static $fieldMap = [];
    /**
     * 需要一并更新修改时间的表
     *
     * @type array
     */
    protected static $touch = [];
    /**
     * 用来更新修改字段名的字段
     *
     * @var string
     */
    protected static $updateAt = '';
    /**
     * 字段名称所用的引号
     *
     * @type string
     */
    protected static $quotes = '"';
    /**
     * 数据库操作次数
     *
     * @type int
     */
    private static $queryTimes = 0;
    /**
     * 搜集的SQL语句
     *
     * @type array
     */
    private $sqlCollect = [];
    /**
     * 查询完成后对结果的处一办法
     *
     * @type array
     */
    private $filterCollect = [];
    /**
     * 生成的PreSql
     *
     * @type string
     */
    private $preSql = '';
    /**
     * 用到的数据
     *
     * @type array
     */
    private $data = [];
    /**
     * 操作中遇到错误时的处理办法 fail,ignore
     *
     * @type string
     */
    private $errorAction = 'fail';
    /**
     * 自定义错误消息列表
     *
     * @type array
     */
    private $errMsgList = [];
    /**
     * 查询完成后是否不清空上次的数据
     *
     * @type bool
     */
    protected $isCleanSqlCollect = true;
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
        'bool'    => ['require', 'increment', 'primary', 'hidden', 'lower', 'del', 'array', 'object'],
        'type'    => ['uuid', 'bit', 'timestamp', 'date'],
        'string'  => ['char', 'varchar', 'text'],
        'numeric' => ['int2', 'int4', 'int8', 'float4', 'float8', 'decimal', 'numeric'],
        'serial'  => ['serial2', 'serial4', 'serial8'],
        'boolean' => ['bool'],
        'object'  => ['json', 'jsonb'],
        'value'   => ['format', 'touch', 'foreign', 'field', 'default', 'validate', 'at', 'filter', 'stuffer'],
        'compare' => ['=', '>', '<', '>=', '<=', '<>', ' like ', ' between ', ' in ']
    ];

    /**
     * 类加载后自动执行的方法
     */
    public static function __init()
    {
        //解析表结构和属性
        self::parseColumns();
    }

    /**
     * 构造Model
     */
    public function __construct()
    {
        $this->connector = new Connector(PROJECT_ROOT, $this->conn);
        static::$quotes  = $this->connector->getQuotes();
        $this->table     = $this->connector->getTablePrefix() . $this->table;
    }

    /**
     * 模型中字段解析器
     */
    private static function parseColumns()
    {
        $parsed = [];
        foreach (static::$columns as $name => $column) {
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
        if (static::$primary && isset($parsed[static::$primary]['increment'])) {
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
        if (!empty($lined['del']) && !isset($lined['hidden'])) {
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
        foreach ($parts as $a) {
            $lined = \array_merge($lined, self::attr($a, $name));
        }
        self::tidyParsedLine($lined);

        return $lined;
    }

    /**
     * 解析规则
     *
     * @param $a
     * @param $name
     * @return array
     * @throws \jt\Exception
     */
    private static function attr($a, $name)
    {
        if (\strpos($a, ':')) {
            list($key, $value) = \explode(':', $a, 2);
        }else {
            list($key, $value) = [$a, null];
        }
        $result = [];
        switch (true) {
            case in_array($key, self::$parseDict['bool']):
                if ($key === 'primary') {
                    static::$primary = $name;
                }
                if ($key === 'del') {
                    $result['type'] = 'bool';
                }
                $result[$key] = true;
                break;
            case in_array($key, self::$parseDict['type']):
                $result['type'] = $key;
                break;
            case in_array($key, self::$parseDict['string']):
                $result['fieldType'] = $key;
                $result['type']      = 'string';
                $result['length']    = intval($value);
                break;
            case in_array($key, self::$parseDict['serial']):
                $result['fieldType'] = $key;
                $result['type']      = 'numeric';
                $result['increment'] = true;
                break;
            case in_array($key, self::$parseDict['numeric']):
                $result['fieldType'] = $key;
                $result['type']      = 'numeric';
                break;
            case in_array($key, self::$parseDict['boolean']):
                $result['type'] = 'bool';
                break;
            case in_array($key, self::$parseDict['object']):
                $result['type'] = 'object';
                break;
            case in_array($key, self::$parseDict['value']):
                if ($key === 'field') {
                    self::$fieldMap[$value] = $name;
                }
                if ($key === 'filter' && !method_exists(get_called_class(), $value)) {
                    self::error('filterNotExists', "数据库模型类 [" . get_called_class() . "] 配置表中 filter 方法[{$value}]不存在，请检查");
                }
                if ($key === 'stuffer' && !method_exists(get_called_class(), $value)) {
                    self::error('stufferNotExists', "数据库模型类 [" . get_called_class() . "] 配置表中 stuffer 方法[{$value}]不存在，请检查");
                }
                $result[$key] = $value;
                break;
            default:
                $class = get_called_class();
                self::error('tableColumnsRulerError', "数据库模型类 [{$class}] 配置表中 [{$name}] 项值 [{$key}] 有误，请检查");
        }

        return $result;
    }

    /**
     * 提交事务
     */
    public static function commit()
    {
        foreach (Connector::getPdoList() as $pdo) {
            self::restartTransaction($pdo);
        }
    }

    /**
     * 提交指连接的事务
     *
     * @param \PDO $pdo
     */
    private static function commitTransaction(\PDO $pdo)
    {
        if (self::$debugMode) {
            (new Action())->header('_db_debug_mode', true);
            $pdo->rollBack();
        }else {
            $pdo->commit();
        }
    }

    /**
     * @param \PDO $pdo
     */
    private static function restartTransaction($pdo)
    {
        if (!$pdo) {
            return;
        }
        if ($pdo->inTransaction()) {
            self::commitTransaction($pdo);
        }
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }

    /**
     * 开始自动事务
     */
    public static function autoCommit()
    {
        foreach (Connector::getPdoList() as $pdo) {
            /* @var $pdo \PDO */
            if ($pdo->inTransaction()) {
                self::commitTransaction($pdo);
            }
        }
    }

    /**
     * 手动开启事务
     */
    public static function beginTransaction()
    {
        foreach (Connector::getPdoList() as $pdo) {
            /* @var $pdo \PDO */
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
        }
    }

    /**
     * 回滚事务
     */
    public static function rollBack()
    {
        foreach (Connector::getPdoList() as $pdo) {
            /* @var $pdo \PDO */
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    /**
     * 与数据库建立连接
     */
    private function connectDb()
    {
        if ($this->pdo === null) {
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
        $sth = $this->pdo->prepare($preSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->setFetchMode(\PDO::FETCH_ASSOC);

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
        switch ($e->getCode()) {
            case '7': //数据库不存在
                $creator = new Schema(PROJECT_ROOT, $this->conn);
                $creator->createDataBase();
            case '42P01': //表不存在
                $creator = new Schema(PROJECT_ROOT, $this->conn);
                $creator->createTable($this->genTableName(), static::$columns);
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
        foreach ($data as $name => $value) {
            $value = is_string($value) ? "'" . $value . "'" : $value;
            $sql   = str_replace(':' . $name, $value, $sql);
        }
        $sql = preg_replace('/\([(?:\?\, )|\?]+\)/', '(\'' . implode('\', \'', $data) . '\')', $sql);

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
        }catch (\PDOException $e){
            self::restartTransaction($this->pdo);
            $this->processError($e, $this->applyExecutableToPreSql($preSql, $data));
            $sth = $this->query($preSql, $data);
        }
        //TODO: $this->logs[] = $sth->queryString; //写入文件
        $this->preSql = '';
        if (self::$debugSql) {
            (new Action())->header('_debug_sql.', $this->applyExecutableToPreSql($preSql, $data));
        }
        if ($this->isCleanSqlCollect) {
            $this->cleanSqlCollect();
        }

        return $sth;
    }

    /**
     * 清除之前操作搜集的参数
     */
    private function cleanSqlCollect()
    {
        $this->sqlCollect = []; //一旦查询完成，清理掉上次用过的数据
        $this->data       = [];
        $this->errMsgList = [];
    }

    /**
     * 整理查询结果
     *
     * @param array $list
     * @return array
     */
    private function combQueryResult(array &$list)
    {
        if (empty($list)) {
            if (!empty($this->filterCollect['fillEmpty'])) {//填充默认的空数据
                foreach ($this->filterCollect['lastCollectedNames'] as $name) {
                    $list[$name] = $this->genEmptyValue($name);
                }
            }

            return $list;
        }

        list(, $item) = each($list);
        $convertStack = [];
        $filterStack  = [];
        foreach (static::$columns as $name => $column) {
            if (!isset($item[$name])) {
                continue;
            }
            if (isset($column['array']) || isset($column['object'])) {
                $convertStack[] = $name;
            }
            if (isset($column['filter'])) {
                $filterStack[$column['filter']] = $name;
            }
        }

        if (!$convertStack && !$filterStack) {
            return $list;
        }
        foreach ($list as &$item) {
            foreach ($convertStack as $name) {
                $item[$name] = json_decode($item[$name], true);
            }

            foreach ($filterStack as $filter => $name) {
                $item[$name] = $this->$filter($item[$name]);
            }
        }

        $this->filterCollect = [];

        return $list;
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
        $sth  = $this->query('SELECT ' . $preSql, $data);
        $list = $sth->fetchAll();

        return $this->combQueryResult($list);
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
        $this->query('INSERT INTO ' . $preSql, $data);
        if ($this->insertId === null && static::$primaryType === 'increment') {
            $this->insertId = $this->pdo->lastInsertId($this->table . '_' . static::$primary . '_seq');
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
        $sth = $this->query('UPDATE ' . $preSql, $data);

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
        $sth = $this->query('DELETE ' . $preSql, $data);

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

    private function mergeSerial($type)
    {
        if (!isset($this->sqlCollect[$type])) {
            return [];
        }
        $list = [];
        foreach ($this->sqlCollect[$type] as $serial) {
            $list = \array_merge($list, \preg_split('/ *, */', $serial));
        }

        return \array_unique($list);
    }

    /**
     * 解析要使用的字段
     */
    private function collectNames()
    {
        if (!isset($this->sqlCollect['names'])) {
            return [];
        }
        $collectedNames = [];

        $showHiddenList = $this->mergeSerial('showHidden');
        $fieldNameList  = $this->mergeSerial('names');
        $excludeFields  = $this->collectExclude();

        if (\in_array('**', $fieldNameList)) {//强制要求列出所有,包含隐藏字段
            foreach (static::$columns as $name => $v) {
                $collectedNames[] = $name;
            }
        }else {
            foreach ($fieldNameList as $n) {
                if ($n === '*') { //如果需要映射到字段，需要在sql中使用as,则不能直接使用"*"
                    foreach (static::$columns as $name => $v) {
                        if (isset($v['hidden']) && (!\in_array($name, $showHiddenList) && !\in_array('*', $showHiddenList))) {
                            continue;
                        }
                        $collectedNames[] = $name;
                    }
                }else {
                    $collectedNames[] = $n;
                }
            }
        }

        $collectedNames = array_unique($collectedNames);
        foreach ($excludeFields as $name) {
            $index = array_search($name, $collectedNames);
            unset($collectedNames[$index]);
        }
        if (!empty($this->filterCollect['fillEmpty'])) { //留着备用
            $this->filterCollect['lastCollectedNames'] = $collectedNames;
        }
        if (count($collectedNames) === count(static::$columns)) {
            $collectedNames = ['*'];
        }

        return $collectedNames;
    }

    /**
     * 生成用于查询用的字段列表
     */
    private function genSelectNames()
    {
        $collectedNames = $this->collectNames();
        $quotes         = static::$quotes;
        foreach ($collectedNames as &$name) {
            if (isset(static::$columns[$name]['field'])) {
                $name = $quotes . static::$columns[$name]['field'] . $quotes . ' AS ' . $quotes . $name . $quotes;
            }elseif ($name !== '*') {
                $name = $quotes . $name . $quotes;
            }
        }
        $this->preSql = ' ' . implode(', ', $collectedNames);
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
        if (isset($column['validate'])) {
            if (!Validate::check($value, $column['validate'])) {
                Error::fatal('DataFormatError', '数据项 [' . $name . '] 格式不正确，期望是一个 [' . $column['validate'] . '],当前给的值为: [' . $value . ']');
            }
            if ($column['validate'] === 'mobile') {
                $value = str_replace('-', '', $value);
            }
        }
        if (isset($columns['array']) || isset($columns['object'])) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }else {
                Error::fatal('DataFormatError', '数据项 [' . $name . '] 期望是一个 [数组],当前给的值为: [' . var_export($value, true) . ']');
            }
        }

        if (!isset($columns['type']) && (isset($columns['createAt']) || isset($columns['updateAt']))) {
            $columns['type'] = 'timestamp';
        } //edit,remove,restore的create_at,update_at没有设置type属性
        $value = $this->convertType($value, $columns['type']);

        return $value;
    }

    /**
     * 转换值类型
     *
     * @param $value
     * @param $type
     *
     * @return mixed
     */
    private function convertType($value, $type)
    {
        switch ($type) {
            case 'timestamp':
                if (is_numeric($value)) {
                    $date = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date->format('Y-m-d\TH:i:s.uP');
                }
                break;
            case 'bool':
                return $value ? 1 : 0;
                break;
        }

        return $value;
    }

    /**
     * 搜集传入的数据
     *
     * @return array
     */
    private function collectData()
    {
        $data = [];
        foreach ($this->sqlCollect['data'] as $item) {
            $data = array_merge($data, $item);
        }

        return $data;
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

        foreach ($partLength as $i => $length) {
            if (isset($default[$i])) {
                $default[$i] = str_pad(substr($default[$i], 0, $length), $length, '0', STR_PAD_LEFT);
            }else {
                $default[$i] = '';
                while (strlen($default[$i]) < $length) {
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
        if (isset($column['default'])) {
            return $column['default'];
        }

        if (isset($column['stuffer'])) {
            $stuffer = $column['stuffer'];

            return $this->$stuffer();
        }

        if (isset($column['at'])) {
            return microtime(true);
        }
        if ($column['type'] === 'uuid' && isset($column['primary'])) {
            return self::genUuid();
        }
        if (isset($column['array']) || isset($column['object'])) {
            return [];
        }

        $type = isset($column['type']) ? $column['type'] : '';
        switch ($type) {
            case 'numeric':
            case 'bool':
            case 'bit':
                return 0;
            default:
                return '';
        }
    }

    /**
     * 生成空值
     *
     * @param $name
     * @return mixed
     */
    private function genEmptyValue($name)
    {
        $type = static::$columns[$name]['type']??'string';
        switch ($type) {
            case 'timestamp':
            case 'numeric':
                return 0;
            case 'bool':
                return false;
            case 'object':
                return [];
            //case 'date':
            //    return '';
            default:
                return '';
        }
    }

    /**
     * 生成插入记录用的属性列表
     */
    private function genInsertNames()
    {
        $data = $this->collectData();
        if (count($data) === 0) {
            return;
        }
        $fields         = [];
        $this->insertId = null;
        foreach (static::$columns as $name => $column) {
            //将属性名与字段名进行映射
            $field = $name;
            if (isset($column['field'])) {
                //if(isset($data[$column['field']])){
                //	//TODO: 仍使用了字段名，给出警告，应该使用属性
                //	$field = $column['field'];
                //}
                $fields[] = $column['field'];
            }else {
                $fields[] = $field;
            }

            if (isset($column['increment'])) {//自增类型
                if (!empty($data[$field])) {
                    self::error('IncrementFieldNotAllowAssignValue', '自增类型字段不允许给值');
                }
                array_pop($fields);
                continue;
            }
            if (empty($data[$field])) {
                if (isset($column['require'])) {
                    self::error('InsertToDataBaseRequire', "表 [{$this->table}] 此项 [{$name}] 不允许为空");
                }
                $data[$field] = $this->genDefaultValue($column);
            }
            $this->data[] = $this->checkData($data[$field], $column, $name);
            if (isset($column['primary'])) {
                $this->insertId = $data[$field];
            }
        }
        //TODO: 记录丢弃的数据
        //TODO: 数据完整性检查
        //TODO: 验证数据
        $placeholder = '?';
        $placeholder .= str_repeat(', ?', count($this->data) - 1);
        $quotes = static::$quotes;
        $this->preSql .= ' (' . $quotes . implode("{$quotes}, {$quotes}", $fields) . $quotes;
        $this->preSql .= ') VALUES (' . $placeholder . ')';
    }

    /**
     * 预处理WHERE语句,为后期生成WHERE语句做准备
     *
     * @return array
     */
    private function preParseWhere()
    {
        $isClosed  = true;
        $whereCode = [];
        $index     = -1;
        foreach ($this->sqlCollect['where'] as $index => $where) { //通观全局
            $whereCode[$index]    = ['', '', ' '];
            $whereCode[$index][1] = $where[2] ? 'OR ' : 'AND ';
            if (!$where[3] && $isClosed) { //括号不关闭 为前一个加括号，如当前为第一个，则为自身加括号
                $pos                = $index >= 1 ? $index - 1 : $index;
                $whereCode[$pos][0] = '(';
                $isClosed           = false;
            }
            if ($where[3] && $isClosed === false) {
                $whereCode[$index][2] = ')';
                $isClosed             = true;
            }
        }
        if (isset($this->sqlCollect['aloneWhere'])) {
            foreach ($this->sqlCollect['aloneWhere'] as $where) { //该条件将独立于其它条件（其它条件用括号括起来）
                $index++;
                $whereCode[$index]    = ['', '', ' '];
                $whereCode[$index][1] = $where[2] ? 'OR ' : 'AND ';
                $whereCode[0][0] .= '( ';
                $whereCode[$index - 1][2] .= ') ';
                $this->sqlCollect['where'][] = $where;
            }
        }
        if ($whereCode) {
            $whereCode[0][1] = '';
        }
        if (!$isClosed) {
            $whereCode[$index][2] = ')';
        }

        return $whereCode;
    }

    /**
     * 通过名字寻找字段的配置信息
     *
     * @param $name
     * @return array|null
     */
    private function findColumnByName($name)
    {
        if (isset(static::$columns[$name])) { //找到了属性
            $column = static::$columns[$name];
        }else {
            if (isset(static::$fieldMap[$name])) {
                $column = static::$columns[static::$fieldMap[$name]];
                //TODO 警告不应在此处使用字段名
            }else {
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
        if (isset($this->sqlCollect['exclude'])) {
            foreach ($this->sqlCollect['exclude'] as $exclude) {
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
        if (isset($this->sqlCollect['ignoreUpdateTime']) && $this->sqlCollect['ignoreUpdateTime']) {
            return;
        }

        foreach (static::$columns as $name => $column) {
            if (isset($column['at']) && $column['at'] === 'update') {
                if (!isset($data[$name])) {
                    $data[$name] = \microtime(true);
                }
            }
        }
    }

    /**
     * 生成表名
     *
     * @return string
     */
    private function genTableName()
    {
        $quotes = static::$quotes;

        return $quotes . str_replace('.', "{$quotes}.{$quotes}", $this->table) . $quotes;
    }

    /**
     * 生成插入记录用的属性列表
     */
    private function genUpdateNames()
    {
        $data = $this->collectData();
        if (count($data) === 0) {
            return;
        }
        $fields = [];

        $this->genUpdateTime($data);

        $fieldValues   = [];
        $excludeFields = $this->collectExclude();

        foreach ($excludeFields as $name) {
            if (isset($data[$name])) {
                unset($data[$name]);
            }
        }

        foreach ($data as $name => $value) {
            if($value === null){
                continue;
            }
            //将属性名与字段名进行映射
            $column = $this->findColumnByName($name);
            if ($column === null || isset($column['primary'])) {//不允许更新主键的内容
                continue;
            }
            $field    = $column['field']??$name;
            $quotes   = static::$quotes;
            $fields[] = $quotes . $field . $quotes;
            if (is_string($value) && substr($value, 0, 1) === '`' && substr($value, -1, 1) === '`') {//值为可执行代码
                $value = trim($value, '` ');
                if (in_array($column['type'], self::$parseDict['numeric']) && preg_match('/^=([\+\-]) *(\d) *$/', $value, $match)) {
                    $fieldValues[] = "{$quotes}$field{$quotes} {$match[1]} {$match[2]}";
                }else {
                    preg_match_all('/(\\\\*)\:(\w+[a-z0-9_\-]*)/i', $value, $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        if ($match[1]) {
                            if (strlen($match[1]) % 2 === 1) {
                                $value = str_replace($match[0], substr($match[1], 0, intval(strlen($match[1]) / 2)) . ':' . $match[2], $value);
                                continue;
                            }else {
                                $match[1] = substr($match[1], 0, strlen($match[1]) / 2);
                            }
                        }
                        $field = $this->nameMapField($match[2]);
                        $value = $match[1] . $field;
                    }
                    $fieldValues[] = $value;
                }
            }else {
                if (is_string($value) && substr($value, 0, 2) === '\`') {
                    $value = substr($value, 1);
                }
                $fieldValues[]             = ':u_' . $field;
                $this->data['u_' . $field] = $this->checkData($value, $column, $name);
            }
        }
        //TODO: 记录丢弃的数据
        //TODO: 数据完整性检查
        //TODO: 验证数据
        $buffer = [];
        foreach ($fields as $index => $f) {
            $buffer[] = "{$f} = {$fieldValues[$index]}";
        }
        $this->preSql .= ' SET ' . implode(', ', $buffer);
    }

    /**
     * 匹配处理条件语句中的字段名
     *
     * @param string $sql
     *
     * @return string
     */
    private function applyAsMapForWhere($sql)
    {
        $conditions = preg_split('/( +and +| +or +)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $l = count($conditions); $i < $l; $i += 2) {
            $conditions[$i] = preg_replace_callback('/^([\( ]*)([\w_]+)(.*?)(:?[\w_]+)([\) ]*)$/', function ($match){
                $bracketStart = str_replace(' ', '', $match[1]);
                $name         = $match[2];
                $sign         = strtoupper(trim($match[3]));
                $value        = $match[4];
                $bracketEnd   = str_replace(' ', '', $match[5]);

                //if (!isset(static::$columns[$name])) {
                //    //TODO 记录不当的属性名
                //}
                $fullField = $this->nameMapField($name);
                if (isset(static::$columns[$name]['lower'])) {
                    $fullField = "lower({$fullField})";
                    $value     = "lower({$value})";
                }

                return "{$bracketStart}{$fullField} {$sign} {$value}{$bracketEnd}";
            }, $conditions[$i]);
            if ($l > $i + 1) {
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
        if (!isset($this->sqlCollect['where'])) {
            return '';
        }
        $whereCode     = $this->preParseWhere();
        $collectedData = [];

        $whereSql = '';
        foreach ($this->sqlCollect['where'] as $index => $where) {
            $sql = $where[0];
            foreach ($where[1] as $k => $v) {
                $sql                              = str_replace(":{$k}", ":w_{$index}_{$k}", $sql);
                $collectedData["w_{$index}_{$k}"] = $v;
            }
            $whereSql .= $whereCode[$index][1] . $whereCode[$index][0] . $sql . $whereCode[$index][2];
        }
        $whereSql = $this->applyAsMapForWhere($whereSql);

        if (isset($this->sqlCollect['subCondition'])) {
            foreach ($this->sqlCollect['subCondition'] as $id => $condition) {
                $whereSql = str_replace($id, $condition, $whereSql);
            }
        }
        $whereSql = ' WHERE ' . $whereSql;
        $this->preSql .= $whereSql;
        $this->data = array_merge($this->data, $collectedData);

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
        if (isset(static::$columns[$name]['field'])) {
            $name = static::$columns[$name]['field'];
        }

        return static::$quotes . $name . static::$quotes;
    }

    /**
     * 生成GROUP
     *
     * @return string
     */
    private function genGroup()
    {
        if (!isset($this->sqlCollect['group'])) {
            return '';
        }
        $groupSql = '';
        foreach ($this->sqlCollect['group'] as $field) {
            $groupSql .= ' GROUP BY ' . $this->nameMapField($field);
        }
        $this->preSql .= $groupSql;

        return $groupSql;
    }

    /**
     * 生成排序规则
     */
    private function genOrder()
    {
        if (!isset($this->sqlCollect['order'])) {
            return;
        }
        $sqlBuffer = [];
        foreach ($this->sqlCollect['order'] as $oa) {
            //$sqlBuffer[] = $this->transfilerAsMap($oa[0]) . ' ' . strtoupper($oa[1]);
            $sqlBuffer[] = $this->nameMapField($oa[0]) . ' ' . strtoupper($oa[1]);
        }

        if ($sqlBuffer) {
            $this->preSql .= ' ORDER BY ' . implode(', ', $sqlBuffer);
        }
    }

    /**
     * 由于PGSQL UPDATE不支持LIMIT语句，在此替代实现
     */
    private function applyLimitForEdit()
    {
        if (isset($this->sqlCollect['limit']) && $this->sqlCollect['limit'][0]) {
            $model                                          = new static();
            $model->sqlCollect                              = $this->sqlCollect;
            $model->sqlCollect['names'][]                   = static::$primary;
            $conditionId                                    = '_sc_79f19ede5b_';
            $this->sqlCollect['subCondition'][$conditionId] = $model->getSelectSql();
            $this->aloneWhere(static::$primary . ' IN (SELECT' . $conditionId . ')');
            $this->data = array_merge($this->data, $model->data);
        }
    }

    /**
     * 生成分页
     */
    private function genLimit()
    {
        if (isset($this->sqlCollect['limit'])) {
            $length = $this->sqlCollect['limit'][0];
            $this->preSql .= " LIMIT {$length}";

            if ($this->sqlCollect['limit'][1] >= 2) {
                $offset = ($this->sqlCollect['limit'][1] - 1) * $length;
                $this->preSql .= " OFFSET {$offset}";
            }
        }
    }

    /**
     * 应用删除标记
     */
    private function applyTrashed()
    {
        $sign = 'hidden';
        if (isset($this->sqlCollect['trashed'])) { //标记为删除的也列出
            $sign = $this->sqlCollect['trashed'];
        }
        if ($sign === 'with') {
            return;
        }
        foreach (static::$columns as $name => $column) {
            if (isset($column['del'])) {
                if ($sign === 'only') {
                    $this->aloneWhere("$name=true");
                }else {
                    $this->aloneWhere("$name=false");
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
        $this->preSql .= ' FROM ' . $this->genTableName();
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
        $this->where(static::$primary . '=:key', ['key' => $primary]);

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

        return isset($res[$name]) ? $res[$name] : null;
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

        return isset($res[$name]) ? $res[$name] : null;
    }

    /**
     * 当未查询到记录时是否返回一条空记录
     *
     * @param bool $is
     * @return $this
     */
    public function fillEmpty($is = true)
    {
        $this->filterCollect['fillEmpty'] = $is;

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
        if ($res) {
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
     * @return array
     */
    public function take($length, $names = '*')
    {
        $this->limit($length);

        return $this->fetch($names);
    }

    private function parseSelectSql()
    {
        $table = $this->genTableName();
        $this->applyTrashed();
        $this->genSelectNames();
        $this->preSql .= ' FROM ' . $table;
        $whereSql = $this->genWhere();
        $groupSql = $this->genGroup();
        $this->genOrder();
        $this->genLimit();

        $this->lastPageInfo = [];

        if (isset($this->sqlCollect['needTotal']) && $this->sqlCollect['needTotal'] && isset($this->sqlCollect['limit'])) {
            $pageSize  = $this->sqlCollect['limit'][0];
            $pageIndex = $this->sqlCollect['limit'][1];

            $this->lastPageInfo = [
                -1,
                $pageIndex,
                $pageSize,
                'COUNT(*) FROM ' . $table . $whereSql . $groupSql,
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
     * 获取结果，连同分页信息
     *
     * @param string $field
     * @return array
     */
    public function fetchWithPage($field = '*')
    {
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
        if ($this->lastPageInfo) {
            if ($this->lastPageInfo[0] === -1) {
                $this->lastPageInfo[0] = $this->select($this->lastPageInfo[3], $this->lastPageInfo[4])[0]['count'];
                unset($this->lastPageInfo[3]);
                unset($this->lastPageInfo[4]);
            }

            return $this->lastPageInfo;
        }else {
            return [];
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
        if ($clean) {
            $this->sqlCollect['names'] = [];
        }
        if ($field) {
            $this->sqlCollect['names'][] = $field;
        }

        return $this;
    }

    /**
     * 将查询结果难叠代器的形式返回
     *
     * @param string $field 获取的字段列表
     *
     * @return \Generator
     */
    public function fetchIterate($field)
    {
        $this->field($field);
        $this->parseSelectSql();

        $sth = $this->query('SELECT ' . $this->preSql, $this->data);

        while ($item = $sth->fetch()) {
            $list = [$item];
            yield $this->combQueryResult($list)[0];
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
        $this->sqlCollect['data'][] = $data;
        $this->preSql               = $this->genTableName();
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
        foreach ($data as $d) {
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
        $this->sqlCollect['data'][] = $data;
        $this->preSql               = $this->genTableName();
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
     * @return $this
     */
    public function pushData(array $data)
    {
        $this->sqlCollect['data'][] = $data;

        return $this;
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
        if ($value < 0) {
            $symbol = '-';
            $value  = abs($value);
        }
        $column = $this->findColumnByName($name);
        if ($column) {
            $field                      = isset($column['field']) ? $column['field'] : $name;
            $this->sqlCollect['data'][] = [$name => "`{$field}{$symbol}{$value}`"];
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
        if ($name === null) {
            $name = static::$primary;
        }
        if (!empty($data[$name])) {
            $row = $this->equals($name, $data[$name])->first($name);
            if ($row) {
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
     * @return int 删除的数量
     */
    public function remove()
    {
        $data = [];
        foreach (static::$columns as $name => $column) {
            if (isset($column['del'])) {
                $data[$name] = true;
                $this->aloneWhere("{$name}=false");
            }
        }
        if (!count($data)) {
            Error::fatal('notDefinedDelField', "表 [{$this->table}] 未定义逻辑删除字段,请检查");
        }

        return $this->edit($data);
    }

    /**
     * 还原删除的数据
     *
     * @return int 还原的条数
     */
    public function restore()
    {
        $data = [];
        foreach (static::$columns as $name => $column) {
            if (isset($column['del'])) {
                $data[$name] = false;
                $this->aloneWhere("{$name}=true");
            }
        }
        if (!count($data)) {
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
        if ($force === true) {
            $this->hiddenTrashed();
        }else {
            $this->onlyTrashed();
        }
        $this->applyTrashed();
        $this->preSql = ' FROM ' . $this->genTableName();
        $this->genWhere();
        $this->genLimit();

        return $this->delete($this->preSql, $this->data);
    }

    /**
     * 获取一条相关数据
     *
     * @param $model
     * @param $foreignKey
     * @param $localKey
     *
     * @return array
     */
    public function hasOne($model, $foreignKey = null, $localKey = null)
    {
        return [];
    }

    /**
     * 获取多条相关的数据
     *
     * @param      $model
     * @param null $foreignKey
     * @param null $localKey
     *
     * @return array
     */
    public function hasMany($model, $foreignKey = null, $localKey = null)
    {
        return [];
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
        $this->preSql .= ' FROM ' . $this->genTableName();
        $this->genWhere();
        $this->genGroup();

        $res = $this->select($this->preSql, $this->data);

        return $res[0]['count'];
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
        $this->where(static::$primary . ' = :primary', ['primary' => $primary]);

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
     *
     * @return $this
     */
    public function where($sql, array $data = [])
    {
        $this->sqlCollect['where'][] = [$sql, $data, 0, 1];

        return $this;
    }

    /**
     * 与之前的条件用OR连接
     *
     * @param string $sql
     * @param array  $data
     *
     * @return $this
     */
    public function orWhere($sql, array $data = [])
    {
        $this->sqlCollect['where'][] = [$sql, $data, 1, 1];

        return $this;
    }

    /**
     * 其余语句需要括起来
     *
     * @param string $sql
     * @param array  $data
     * @param string $link
     *
     * @return $this
     */
    public function aloneWhere($sql, array $data = [], $link = 'and')
    {
        $this->sqlCollect['aloneWhere'][] = [$sql, $data, $link === 'and' ? 0 : 1];

        return $this;
    }

    /**
     * 附着在前一个Where子句内的条件
     *
     * @param string $sql
     * @param array  $data
     * @param string $link 与前方条件的结合方式
     *
     * @return $this
     */
    public function affixWhere($sql, array $data = [], $link = 'and')
    {
        $this->sqlCollect['where'][] = [$sql, $data, $link === 'and' ? 0 : 1, 0];

        return $this;
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
        $this->where("$name=:$name", [$name => $value]);

        return $this;
    }

    /**
     * 相等条件
     *
     * @param array $list
     *
     * @return $this
     */
    public function equalsMulti($list, $glue = 'and')
    {
        $buffer = [];
        foreach ($list as $name => $value) {
            $buffer[] = "$name=:$name";
        }
        $this->where(implode(" {$glue} ", $buffer), $list);

        return $this;
    }

    /**
     * 模糊搜索
     */
    public function like()
    {

    }

    /**
     * 搜索
     *
     * @param string $condition 搜索条件 ['name', 'like', '%:name%', 'or']
     * @param array  $data 搜索用的值
     *
     * @return $this
     */
    public function search($condition, $data)
    {
        //TODO:转换成where语句
        //$this->sqlCollect['page'] = [$length, $page];
        return $this;
    }

    /**
     * 搜索条件与之前条件间用OR连接
     *
     * @param string $condition 搜索条件 ['name', 'like', '%:name%', 'or']
     * @param array  $data 搜索用的值
     *
     * @return $this
     */
    public function orSearch($condition, $data)
    {
        //TODO:转换成where语句
        //$this->sqlCollect['page'] = [$length, $page];
        return $this;
    }

    /**
     * 嵌入到之前条件内
     *
     * @param string $condition 搜索条件 ['name', 'like', '%:name%', 'or']
     * @param array  $data 搜索用的值
     * @param string $link 与前方条件的结合方式
     *
     * @return $this
     */
    public function affixSearch($condition, $data, $link = 'and')
    {
        //TODO:转换成where语句
        //$this->sqlCollect['page'] = [$length, $page];
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
        if ($pageSize === null) {
            if (isset($this->sqlCollect['limit'])) {
                $pageSize = $this->sqlCollect['limit'][1];
            }else {
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
     * @return $this
     */
    public function withHidden($field = '*')
    {
        $this->sqlCollect['showHidden'][] = $field;

        return $this;
    }

    /**
     * 关联查询
     *
     * @param string $model
     * @param string $on
     * @param array  $data
     * @param string $names
     *
     * @return $this
     */
    public function innerJoin($model, $on, array $data = [], $names = '*')
    {
        $this->sqlCollect['innerJoin'][] = [$model, $on, $data, $names];

        return $this;
    }

    /**
     * 关联查询
     *
     * @param string $model
     * @param string $on
     * @param array  $data
     * @param string $names
     *
     * @return $this
     */
    public function leftJoin($model, $on, array $data = [], $names = '*')
    {
        $this->sqlCollect['leftJoin'][] = [$model, $on, $data, $names];

        return $this;
    }

    /**
     * 关联查询
     *
     * @param string $model
     * @param string $on
     * @param array  $data
     * @param string $names
     *
     * @return $this
     */
    public function rightJoin($model, $on, array $data = [], $names = '*')
    {
        $this->sqlCollect['rightJoin'][] = [$model, $on, $data, $names];

        return $this;
    }

    /**
     * exists查询
     *
     * @param string $model
     * @param string $where
     * @param array  $data
     * @param string $names
     *
     * @return $this
     */
    public function exists($model, $where, array $data = [], $names = '*')
    {
        $this->sqlCollect['exists'][] = [$model, $where, $data, $names];

        return $this;
    }

    /**
     * in 查询
     *
     * @param string $field 要查的字段
     * @param array  $list 值列表 可以是索引数组
     *
     * @return $this
     */
    public function in($field, array $list)
    {
        $keys = [];
        foreach ($list as $k => $v) {
            $keys[] = ":{$k}";
        }
        $instr = implode(',', $keys);
        $this->aloneWhere("$field in ({$instr})", $list);

        return $this;
    }

    /**
     * 设置GROUP
     *
     * @param $field
     *
     * @return $this
     */
    public function group($field)
    {
        $this->sqlCollect['group'][] = $field;

        return $this;
    }

    /**
     * 排序
     *
     * @param string $attr 排序的属性(将自动映射为字段)
     * @param string $order asc | desc
     * @param null   $model 以指定的模块的属性排序
     *
     * @return $this
     */
    public function order($attr, $order = 'asc', $model = null)
    {
        $fields = \preg_split('/ *, */', $attr);
        foreach ($fields as $field) {
            $this->sqlCollect['order'][] = [$field, $order, $model];
        }

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
        $te = new Exception($code . ':' . $msg);
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
    public static function startDebug($commit = false, $printSql = true)
    {
        self::$debugMode = !$commit;
        self::$debugSql  = $printSql;
    }
}