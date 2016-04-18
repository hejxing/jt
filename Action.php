<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-4 01:36
 */

namespace jt;

define('FILL_DATA_OVER', 1);
define('FILL_DATA_IGNORE', 2);
define('FILL_DATA_APPEND', 3);
define('FILL_DATA_PREPEND', 4);
define('FILL_DATA_JOIN_RIGHT', 5);
define('FILL_DATA_JOIN_LEFT', 6);

/**
 * Action基类
 * 负责处理客户端的调用
 *
 * @package jt
 */
class Action
{
    /**
     * 参数验证规则
     *
     * @type array
     */
    protected static $validate = [];
    /**
     * 存放要响应给客户端的数据
     *
     * @var array
     */
    static private $dataStore = [];
    /**
     * 存放要响应给客户端的状态信息
     *
     * @var string
     */
    static private $headerStore = [];
    /**
     * 保存中间变量，用来在方法之间传值
     *
     * @type array
     */
    protected $valueStore = [];
    /**
     * 是否执行完成
     *
     * @type bool
     */
    protected static $runComplete = false;
    /**
     * 本次操作是否成功
     *
     * @type bool
     */
    private static $taskSuccess = true;

    /**
     * 设置回复的头部数据
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $model
     *
     * @return bool
     */
    public function header($key, $value, $model = FILL_DATA_OVER)
    {
        return self::fillData(self::$headerStore, $key, $value, $model);
    }

    /**
     * 需要输出的数据
     *
     * @param string $key
     * @param string $value
     * @param int    $model
     *
     * @return bool
     */
    public function out($key, $value, $model = FILL_DATA_OVER)
    {
        return self::fillData(self::$dataStore, $key, $value, $model);
    }

    /**
     * 批量绑定要输出的数据
     *
     * @param array $data
     * @param int   $model 添加方式
     */
    public function outMass(array $data, $model = FILL_DATA_OVER)
    {
        foreach ($data as $key => $value) {
            self::fillData(self::$dataStore, $key, $value, $model);
        }
    }

    /**
     * @param array $data
     * @param mixed $key
     * @param mixed $value
     * @param int   $model
     *
     * @return bool
     */
    private function fillData(array &$data, $key, $value, $model)
    {
        if (\is_int($key) || $key === null) {
            $data[] = $value;

            return true;
        }

        $parts = explode('.', $key);
        $key = array_pop($parts);
        foreach ($parts as $p) {
            if(!$p){
                $p = count($data);
            }
            if(!isset($data[$p])){
                $data[$p] = [];
            }
            $data = &$data[$p];
        }
        if(!$key){
            $key = count($data);
        }
        switch ($model) {
            case FILL_DATA_OVER:
                $data[$key] = $value;
                break;
            case FILL_DATA_IGNORE:
                if (isset($data[$key])) {
                    return false;
                }else {
                    $data[$key] = $value;
                }
                break;
            case FILL_DATA_APPEND:
            case FILL_DATA_PREPEND:
                if (!isset($data[$key])) {
                    $data[$key] = [];
                }
                if (!is_array($data[$key])) {
                    return false;
                }
                if ($model === FILL_DATA_APPEND) {
                    array_push($data[$key], $value);
                }else {
                    array_unshift($data[$key], $value);
                }
                break;
            case FILL_DATA_JOIN_RIGHT:
            case FILL_DATA_JOIN_LEFT:
                if (!\is_string($value) || (isset($data[$key]) && !\is_string($data[$key]))) {
                    return false;
                }
                if (isset($data[$key])) {
                    if ($model === FILL_DATA_JOIN_RIGHT) {
                        $data[$key] .= $value;
                    }else {
                        $data[$key] = $value . $data[$key];
                    }
                }else {
                    $data[$key] = $value;
                }
                break;
        }

        return true;
    }

    /**
     * 获取头部数据
     *
     * @return string
     */
    public static function getHeaderStore()
    {
        $headerStore = self::$headerStore;
        //>debug
        if (RUN_MODE === 'develop') {
            $headerStore['queryCount']     = class_exists('\jt\Model', false) ? Model::getQueryTimes() : 0;// + \dal\Dal::selectQueryTimes();
            $includeFiles                  = get_included_files();
            $headerStore['loadFilesCount'] = count($includeFiles);
        }
        //debug<
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $size = memory_get_usage(true) / 8;
        $i    = (int)floor(log($size, 1024));

        $headerStore['useMemory'] = round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
        $headerStore['spendTime'] = intval((microtime(true) - Bootstrap::$startTime) * 1000);

        return $headerStore;
    }

    /**
     * 获取数据
     *
     * @return array
     */
    public static function getDataStore()
    {
        return self::$dataStore;
    }

    /**
     * 获取数据区的值
     *
     * @param $name
     *
     * @return mixed
     */
    public function getFromData($name)
    {
        if (isset(self::$dataStore[$name])) {
            return self::$dataStore[$name];
        }else {
            return null;
        }
    }

    /**
     * 获取头部区的值
     *
     * @param $name
     *
     * @return mixed
     */
    public function getFromHead($name)
    {
        if (isset(self::$headerStore[$name])) {
            return self::$headerStore[$name];
        }else {
            return null;
        }
    }

    /**
     * 保存中间值
     *
     * @param      $name
     * @param      $value
     */
    public function __set($name, $value)
    {
        $this->valueStore[$name] = $value;
    }

    /**
     * 获取保存的中间值
     *
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        if (isset($this->valueStore[$name])) {
            return $this->valueStore[$name];
        }

        return null;
    }

    /**
     * 清空数据
     */
    public static function cleanData()
    {
        Controller::current()->getAction()->valueStore = [];
        self::$dataStore = [];
        self::$headerStore = [];
    }

    /**
     * 在业务执行前执行
     *
     * @param string $method
     * @param array  $param
     * @return bool
     */
    public function before($method, $param)
    {
        return true;
    }

    /**
     * 业务执行完毕，在内容输出前执行
     *
     * @param string $method
     * @param array  $param
     * @return bool
     */
    public function after($method, $param)
    {
        return true;
    }

    /**
     * 设置是否执行完成
     *
     * @param $isComplete
     */
    public static function setIsRunComplete($isComplete)
    {
        self::$runComplete = $isComplete;
    }

    /**
     * 获取是否执行完成
     *
     * @return bool
     */
    public static function isRunComplete()
    {
        return self::$runComplete;
    }

    /**
     * 设置操作成功说明
     *
     * @param string $msg 操作说明
     * @param string $code 错误代码
     * @param bool   $responseEnd 是否结束响应，立即返回
     *
     */
    public function success($msg, $code = '', $responseEnd = false)
    {
        $this->header('msg', $msg);
        if ($code) {
            $this->header('code', $code);
        }
        if ($responseEnd) {
            Responder::end();
        }
    }

    /**
     * 置本次操作于失败状态
     *
     * @param string $msg 失败原因
     * @param string $code 错误代码
     * @param array  $param 传递的参数
     * @param int    $status 错误状态
     * @param bool   $responseEnd 是否结束响应，立即返回
     * @throws Exception
     */
    public function fail($msg, $code = 'fail', $param = [], $status = null, $responseEnd = true)
    {
        self::$taskSuccess = false;
        if ($status) {
            header('Status: ' . $status, true);
        }
        if ($responseEnd) {
            $e = new Exception("{$code}:{$msg}");
            $e->setType('taskFail');
            $e->setParam($param);
            $e->setIgnoreTraceLine(1);
            throw $e;
        }else {
            $this->header('code', $code);
            $this->header('msg', $msg);
        }
    }

    /**
     * 操作是否成功
     *
     * @return bool
     */
    public static function isSuccess()
    {
        return self::$taskSuccess;
    }

    /**
     * 获取参数验证规则
     *
     * @return array
     */
    public function getValidate()
    {
        return static::$validate;
    }

    /**
     * 将原数组名称映射到新键名
     *
     * @param array $map
     * @param array $data
     * @param bool  $ignoreEmpty 是否忽略空值
     *
     * @return array
     */
    protected function map(array $map, array $data, $ignoreEmpty = false)
    {
        $result     = [];
        $indexAssoc = false;
        foreach ($map as $n => $v) {
            if (\is_int($n)) {
                $n          = $v;
                $indexAssoc = true;
            }
            $type = 'string';
            \preg_match('/^\((.*)\)(.+)/', $n, $matched);
            if (\count($matched) > 2) {
                $type = $matched[1];
                $n    = $matched[2];
                if ($indexAssoc) {
                    $v = $n;
                }
            }
            $vns   = \explode('.', $v);
            $value = $data;
            foreach ($vns as $vn) {

                if (isset($value[$vn])) {
                    $value = $value[$vn];
                }elseif (\substr($vn, 0, 1) === '"' && \substr($vn, -1, 1) === '"') {
                    $value = \substr($vn, 1, -1);
                }else {
                    $value = null;
                    break;
                }
            }
            if ($value === null && $ignoreEmpty) {
                continue;
            }
            switch ($type) {
                case 'int':
                    $value = intval($value ?: 0);
                    break;
                case 'float':
                    $value = floatval($value ?: 0);
                    break;
                case 'bool':
                    $value = boolval($value);
                    break;
                default:
                    $value = $value === null ? '' : $value;
                    break;
            }
            $result[$n] = $value;
        }

        return $result;
    }

    /**
     * 将二维原数组名称映射到新键名 可限制返回的数据最大条数和最小条数，当不足最小条数时，补空数据
     *
     * @param array $map
     * @param array $data
     * @param array $option
     *
     * @return array
     */
    protected function mapMulti(array $map, array $data, array $option = [])
    {
        $result = [];
        $option = \array_merge([
            'minCount' => -1,
            'maxCount' => 9999999
        ], $option);
        foreach ($data as $key => $item) {
            $result[$key] = $this->map($map, $item);
            if (\sizeof($result) >= $option['maxCount']) {
                break;
            }
        }

        while (\sizeof($result) < $option['minCount']) {
            $result[] = $this->map($map, []);
        }

        return $result;
    }

    /**
     * 输出操作结果
     *
     * @param bool  $success 成功状态
     * @param array $successMsg 成功信息数组
     * @param array $failMsg 失败信息数组
     */
    public function outResult($success, array $successMsg, array $failMsg)
    {
        if ($success) {
            $this->success($successMsg[1], $successMsg[0]);
        }else {
            $this->fail($failMsg[1], $failMsg[0]);
        }
    }

    /**
     * 设置记录总长度
     *
     * @param int $total
     * @param int $size
     * @param int $page
     */
    public function outTotal($total, $size, $page)
    {
        $this->out('total', \intval($total));
        $this->out('size', \intval($size));
        $this->out('page', \intval($page));
    }

    /**
     * 输出带分页信息的列表
     *
     * @param array $list
     * @param array $page 分页信息 其值的顺序为 total,size,index
     */
    public function outList(array $list, array $page)
    {
        $this->outTotal($page[0], $page[1], $page[2]);
        $this->out('list', $list);
    }

    /**
     * 加载后自动初始化工作
     *
     * @param string $className 加载时用到的类名
     */
    public static function __init($className)
    {

    }

    /**
     * 文档输出类型
     *
     * @param $mime
     */
    public function setMime($mime)
    {
        Controller::current()->setMime($mime);
    }

    /**
     * 设置所用的模板
     *
     * @param $file
     */
    public function setTpl($file)
    {
        Controller::current()->setTemplate($file);
    }

    /**
     * 不输出内容
     */
    public function quiet()
    {
        Controller::current()->quiet();
    }

    /**
     * 跳转
     *
     * @param     $url
     * @param int $status
     */
    public function redirect($url, $status = 302)
    {
        Responder::redirect($url, $status);
    }

    /**
     * 设置请求返回状态，默认为200
     *
     * @param int   $status 状态码
     * @param array $param 请求传递的参数
     * @param bool  $error 是否判定为错误
     * @throws Exception
     */
    public function status($status, $param = [], $error = true)
    {
        \header('Status: ' . $status);
        if ($status >= 400 && $error) {
            self::$taskSuccess = false;

            $e = new Exception($status . ':');
            $e->setParam($param);
            throw $e;
        }
    }

    /**
     * @param Action $obj
     * @param string $method
     * @param array  ...$param
     */
    protected function invoke(Action $obj, $method, ...$param)
    {
        //TODO 对参数规则进行验证
        call_user_func_array([$obj, $method], $param);
    }

    /**
     * 初始化Action
     *
     * @return bool
     */
    public function init()
    {
        return true;
    }

    /**
     * 获取当前Action实例
     * @return \jt\Action
     */
    public static function current(){
        return Controller::current()->getAction();
    }
}