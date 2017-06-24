<?php
/**
 * Auth: ax
 * Date: 15-4-4 01:36
 */

namespace jt;

use jt\utils\Helper;

/**
 * Action基类
 * 负责处理客户端的调用
 *
 * @package jt
 */
class Action
{
    const FILL_OVER         = 1;
    const FILL_IGNORE       = 2;
    const FILL_APPEND       = 3;
    const FILL_PREPEND      = 4;
    const FILL_JOIN_RIGHT   = 5;
    const FILL_JOIN_LEFT    = 6;
    const FILL_IGNORE_EMPTY = 7;

    /**
     * 参数验证规则
     *
     * @type array
     */
    protected $validate = [];
    /**
     * 存放要响应给客户端的数据
     *
     * @var array
     */
    private $dataStore = [];
    /**
     * 存放要响应给客户端的状态信息
     *
     * @var array
     */
    private $headerStore = [];
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
    protected $runComplete = false;
    /**
     * @var Controller
     */
    protected $controller = null;
    /**
     * 本次操作是否成功
     *
     * @type bool
     */
    private $taskSuccess = true;
    /**
     * @var bool 是否缓存，与模板引擎配合使用
     */
    private $isCache = false;

    /**
     * 设置回复的头部数据
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $model
     *
     * @return bool
     */
    public function header($key, $value, $model = self::FILL_OVER)
    {
        return $this->fillData($this->headerStore, $key, $value, $model);
    }

    /**
     * 需要输出的数据
     *
     * @param string $key
     * @param mixed $value
     * @param int    $model
     *
     * @return bool
     */
    public function out($key, $value, $model = self::FILL_OVER)
    {
        return $this->fillData($this->dataStore, $key, $value, $model);
    }

    /**
     * 批量绑定要输出的数据
     *
     * @param array $data
     * @param int   $model 添加方式
     */
    public function outMass(array $data, $model = self::FILL_OVER)
    {
        foreach($data as $key => $value){
            $this->fillData($this->dataStore, $key, $value, $model);
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
        if($this->isCache){
            $data = &$data['__cache'];
        }
        if($key === null || (\is_int($key) && $key === count($data))){
            $data[] = $value;

            return true;
        }

        $parts = explode('.', $key);
        $key   = array_pop($parts);
        foreach($parts as $p){
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
        switch($model){
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::FILL_IGNORE:
                if(isset($data[$key])){
                    return false;
                }
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::FILL_IGNORE_EMPTY:
                if(($value === null || $value === '') && isset($data[$key])){
                    return false;
                }
            case self::FILL_OVER:
                $data[$key] = $value;
                break;
            case self::FILL_APPEND:
            case self::FILL_PREPEND:
                if(!isset($data[$key])){
                    $data[$key] = [];
                }
                if(!is_array($data[$key])){
                    return false;
                }
                if($model === self::FILL_APPEND){
                    array_push($data[$key], $value);
                }else{
                    array_unshift($data[$key], $value);
                }
                break;
            case self::FILL_JOIN_RIGHT:
            case self::FILL_JOIN_LEFT:
                if(!is_string($value) || (isset($data[$key]) && !is_string($data[$key]))){
                    return false;
                }
                if(isset($data[$key])){
                    if($model === self::FILL_JOIN_RIGHT){
                        $data[$key] .= $value;
                    }else{
                        $data[$key] = $value.$data[$key];
                    }
                }else{
                    $data[$key] = $value;
                }
                break;
        }

        return true;
    }

    /**
     * 获取头部数据
     *
     * @return array
     */
    public function getHeaderStore()
    {
        return $this->headerStore;
    }

    /**
     * 获取数据
     *
     * @param bool $filter 是否过滤结果
     *
     * @return array
     */
    public function getDataStore($filter = true)
    {
        if($filter === false){
            return $this->dataStore;
        }
        $ruler       = Controller::current()->getRuler();
        $returnRuler = $ruler[6]??[];
        if(empty($returnRuler) || !$this->runComplete || !$this->taskSuccess){
            return $this->dataStore;
        }
        //if (empty(self::$dataStore)) {
        //    (new self())->header('empty', true);
        //}

        return Requester::revisionOutput($this->dataStore, $returnRuler);
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
        if(isset($this->dataStore[$name])){
            return $this->dataStore[$name];
        }else{
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
        if(isset($this->headerStore[$name])){
            return $this->headerStore[$name];
        }else{
            return null;
        }
    }

    /**
     * 清空数据
     */
    public function cleanData()
    {
        $this->valueStore  = [];
        $this->dataStore   = [];
        $this->headerStore = [];
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
     * @param bool $isComplete
     */
    public function setIsRunComplete($isComplete)
    {
        $this->runComplete = $isComplete;
    }

    /**
     * 设置是否执行成功
     *
     * @param bool $success
     */
    public function setIsSuccess($success)
    {
        $this->taskSuccess = $success;
    }

    /**
     * 获取是否执行完成
     *
     * @return bool
     */
    public function isRunComplete()
    {
        return $this->runComplete;
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
        if($code){
            $this->header('code', $code);
        }
        if($responseEnd){
            Responder::end();
        }
    }

    /**
     * 置本次操作于失败状态
     *
     * @param string $msg 失败原因
     * @param string $code 错误代码
     * @param array  $data 输出的内容
     * @param int    $status 错误状态
     * @param bool   $responseEnd 是否结束响应，立即返回
     * @throws Exception
     */
    public function fail($msg, $code = 'fail', $data = [], $status = null, $responseEnd = true)
    {
        $this->taskSuccess = false;
        if($status){
            header('Status: '.$status, true);
        }
        if($responseEnd){
            $e = new Exception("{$code}:{$msg}");
            $e->setType('taskFail');
            $e->addData($data);
            $e->setIgnoreTraceLine(1);
            throw $e;
        }else{
            $this->header('code', $code);
            $this->header('msg', $msg);
        }
    }

    /**
     * 操作是否成功
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->taskSuccess;
    }

    /**
     * 获取参数验证规则
     *
     * @return array
     */
    public function getValidate()
    {
        return $this->validate;
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
        return Helper::map($map, $data, $ignoreEmpty);
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
        foreach($data as $key => $item){
            $result[$key] = $this->map($map, $item);
            if(\sizeof($result) >= $option['maxCount']){
                break;
            }
        }

        while(\sizeof($result) < $option['minCount']){
            $result[] = $this->map($map, []);
        }

        return $result;
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
        $this->out('total', intval($total));
        $this->out('size', intval($size));
        $this->out('page', intval($page));
    }

    /**
     * 输出带分页信息的列表
     *
     * @param array $list
     * @param array $page 分页信息 其值的顺序为 total,size,page
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
     *
     * @param bool $quiet
     */
    public function quiet($quiet = true)
    {
        Controller::current()->quiet($quiet);
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
     * @param int    $status 状态码
     * @param string $msg
     * @param array  $param 请求传递的参数
     * @param bool   $error 是否判定为错误
     * @throws Exception
     */
    public function status($status, $msg = '', $param = [], $error = true)
    {
        header('Status: '.$status);
        if($status >= 400 && $error){
            $this->taskSuccess = false;

            $e = new Exception($status.':'.$msg);
            $e->setType('taskFail');
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
     * 设置当前的Controller
     *
     * @param Controller $controller
     */
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
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
     * 此后的数据可以缓存
     *
     * @throws \jt\Exception
     */
    public function cacheBegin()
    {
        //@>develop
        if(RUN_MODE === 'develop'){
            return;
        }
        //@<develop
        $method = $this->controller->getRequestMethod();
        if($method !== 'get'){
            throw new Exception('NotAllowCache:Request method: '.$method.' not allow cache!');
        }
        $this->isCache = true;
        $responder     = $this->controller->getResponder();
        if($responder->hadCache()){
            $responder->end();
        }
        //判断是否已经有缓存了
        $this->dataStore['__cache']   = [];
        $this->headerStore['__cache'] = [];
    }
}