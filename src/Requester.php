<?php
/**
 * Created by ax@csmall.com.
 * Date: 2015/6/12 13:19
 *
 * 控制请求的输入输出
 *
 */

namespace jt;

use jt\utils\HtmlPurifier;

/**
 * 处理外部获取到的参数
 *
 * @package jt
 */
class Requester
{
    protected $originData = [];
    protected $ruler      = [];
    protected $valueCache = [];
    /**
     * @type array
     */
    protected $useNameMap = null;

    protected $method = '';

    const CONVERT_TYPE  = ['string', 'int', 'float', 'double', 'bool', 'money', 'datetime', 'timestamp'];
    const VALIDATE_TYPE = ['email', 'mobile', 'phone', 'identityCard', 'number', 'zn_ch', 'uuid'];

    const VALUE_RANGE_TYPE  = ['int', 'float', 'money', 'double'];
    const LENGTH_RANGE_TYPE = ['string'];

    const FALSE_VALUE = ['n', 'f', 'no', 'false'];

    const TRUE_ITEM     = ['require', 'lower', 'upper', 'unTrim', 'unEncode', 'unClean', 'unConvert', 'raw', 'page', 'unreal'];
    const INPUT_TYPE    = ['any', 'get', 'post', 'path'];
    const SINGLE_TYPE   = ['enum', 'bool', 'json', 'xml', 'html'];//合并上CONVERT_TYPE
    const MULTI_TYPE    = ['object', 'objectList', 'list'];
    const INJECT_VALUE  = ['instance', 'param'];
    const VALUE_RULE    = ['default', 'format', 'validate', 'use', 'convert', 'min', 'max', 'filter'];
    const FORMAT_ENABLE = ['datetime', 'float', 'money', 'html'];

    /**
     * 模型中字段解析器
     *
     * @param string $strRuler 规则字串
     * @param string $name 参数名称 用于显示友好的错误提示
     *
     * @return array
     */
    public static function parseValidate($strRuler, $name)
    {
        $lined = ['rule' => $strRuler, 'type' => 'undefined'];
        $parts = preg_split('/ +/', $strRuler);
        foreach($parts as $a){
            if($a){
                $res = self::attr($a, $name);
                if(isset($res['type']) && in_array($res['type'], self::MULTI_TYPE) && $lined['type'] !== 'undefined'){
                    $lined['inType'] = $lined['type'];
                }
                if(in_array($lined['type'], self::MULTI_TYPE) && isset($res['type'])){
                    $lined['inType'] = $res['type'];
                    unset($res['type']);
                }
                $lined = array_merge($lined, $res);
            }
        }

        if($lined['type'] === 'undefined'){
            $lined['type'] = 'string';
            $lined['rule'] = trim('string '.$lined['rule']);
        }

        if(!isset($lined['format'])){
            $lined['format'] = null;
        }

        if(in_array($lined['type'], self::VALUE_RANGE_TYPE) || in_array($lined['type'],
                self::LENGTH_RANGE_TYPE) || (isset($lined['inType']) && (in_array($lined['inType'],
                        self::VALUE_RANGE_TYPE) || in_array($lined['inType'], self::LENGTH_RANGE_TYPE)))
        ){
            if(!isset($lined['min'])){
                $lined['min'] = 0;
            }
            if(!isset($lined['max'])){
                $lined['max'] = 0;
            }
        }

        return $lined;
    }

    /**
     * 解析规则属性
     *
     * @param string $a
     * @param string $name
     * @return array
     * @throws Exception
     */
    private static function attr($a, $name)
    {
        if(strpos($a, ':')){
            list($key, $value) = explode(':', $a, 2);
        }else{
            list($key, $value) = [$a, null];
        }

        $result = [];
        switch(true){
            case in_array($key, self::TRUE_ITEM):
            case in_array($key, self::INPUT_TYPE):
                $result[$key] = true;
                break;
            case in_array($key, self::MULTI_TYPE):
            case in_array($key, self::SINGLE_TYPE):
            case in_array($key, self::CONVERT_TYPE):
                if($key === 'enum'){
                    $result[$key] = preg_split('/ *, */', $value);
                }else{
                    $result['type'] = $key;
                    if(in_array($key, self::FORMAT_ENABLE) && $value){
                        $result['format'] = $value;
                    }
                }
                break;
            case $key === 'type':
                $value = $value?: 'string';
                if(in_array($value, self::SINGLE_TYPE) || in_array($value, self::MULTI_TYPE) || in_array($value, self::CONVERT_TYPE)){
                    $result['type'] = $value;
                }elseif($value === 'array'){
                    $result['type'] = 'objectList';
                }else{
                    throw new Exception("actionRulerValueError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 的属性 [{$value}] 有误，请检查");
                }
                break;
            case in_array($key, self::VALUE_RULE):
                $result[$key] = $value;
                break;
            case in_array($key, self::VALIDATE_TYPE):
                $result['validate'] = $key;
                break;
            case in_array($key, self::INJECT_VALUE):
                if($key === 'param'){
                    $result[$key] = preg_split('/ *, */', $value);
                }else{
                    $result[$key] = $value;
                }
                break;
            default:
                throw new Exception("actionRulerError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 有误，请检查");
                break;
        }

        return $result;
    }

    /**
     * 获取参数
     *
     * @param string $name
     * @param string $ruler
     * @return mixed
     */
    public function get($name, $ruler = null)
    {
        if($ruler === null){
            return $this->__get($name);
        }else{
            return $this->value($name, self::parseValidate($ruler, $name));
        }
    }

    /**
     * @param mixed  $data
     * @param array  $ruler
     * @param string $name
     * @param bool   $safeCheck
     *
     * @return array
     */
    private static function compositeConvert($data, $ruler, $name = '', $safeCheck)
    {
        if(isset($ruler['raw']) && $ruler['raw']){
            return $data;
        }
        switch($ruler['type']){
            case 'object':
                $buffer = [];
                if(isset($ruler['nodes'])){
                    foreach($ruler['nodes'] as $field => $r){
                        $buffer[$field] = self::validate($data[$field]??null, $r, $name.'.'.$field, $safeCheck);
                    }
                }

                return $buffer;
            case 'objectList':
                $buffer        = [];
                $ruler['type'] = 'object';
                foreach($data as $d){
                    $buffer[] = self::compositeConvert($d, $ruler, $name, $safeCheck);
                }

                return $buffer;
            case 'list':
                $buffer = [];
                if(is_array($data)){
                    foreach($data as $d){
                        $buffer[] = isset($ruler['inType'])? self::validate($d, $ruler, $name, $safeCheck): $d;
                    }
                }

                return $buffer;
        }

        return [];
    }

    /**
     * 验证某值是否合法,如果合法返回值，不合法报错
     *
     * @param mixed  $value
     * @param array  $ruler
     * @param string $name
     * @param bool   $safeCheck
     *
     * @return mixed
     */
    public static function validate($value, $ruler, $name = '', $safeCheck = true)
    {
        if($value === null){
            if(isset($ruler['default'])){
                $value = $ruler['default'];
            }elseif(isset($ruler['require'])){
                self::error('value_empty', '该项值必填', $name, $ruler);
            }else{
                return self::fillEmpty($ruler);
            }
        }

        if(isset($ruler['raw'])){
            return $safeCheck? self::safeProcess($value): $value;
        }

        if(in_array($ruler['type'], self::MULTI_TYPE)){
            $value = self::compositeConvert(self::revisionValue($value, $ruler), $ruler, $name, $safeCheck);
        }elseif(in_array($ruler['type'], self::CONVERT_TYPE)){ //转换类型
            $value = self::convert($value, $ruler['type'], $ruler['format']);
        }

        if(isset($ruler['validate'])){
            $result = Validate::check($value, $ruler['validate'], true);
            if($result === false){
                if($ruler['validate'] === 'uuid' && $value === '0'){
                    $value = '00000000-0000-0000-0000-000000000000';
                }else{
                    self::error('value_validate_invalid', '值只允许是 ['.$ruler['validate'].']', $name, $ruler);
                }
            }elseif($result === null){
                self::error('value_validate_type_invalid', '验证规则无效 ['.$ruler['validate'].'],需要有效的规则或正则表达式', $name, $ruler);
            }
        }elseif($ruler['type'] === 'html'){
            $purifier = new HtmlPurifier($ruler['format']);
            $value    = $purifier->process($value);
        }else{
            if(!self::typeCheck($value, $ruler['type'])){
                self::error('value_type_invalid', '需要类型为 ['.$ruler['type'].'] 的值', $name, $ruler);
            }
            if($safeCheck){
                $value = self::safeProcess($value);
            }
        }

        if(isset($ruler['enum']) && !in_array($value, $ruler['enum'])){
            self::error('value_over', '只能从 ['.implode(', ', $ruler['enum']).'] 中取值', $name, $ruler);
        }

        if(in_array($ruler['type'], static::VALUE_RANGE_TYPE)){
            if($ruler['min'] && $value < $ruler['min']){
                self::error('value_too_less', '值不能小于 '.$ruler['min'], $name, $ruler);
            }
            if($ruler['max'] && $value > $ruler['max']){
                self::error('value_too_large', '值不能大于 '.$ruler['max'], $name, $ruler);
            }
        }

        if(in_array($ruler['type'], static::LENGTH_RANGE_TYPE)){//比较长度
            if($ruler['min'] && mb_strlen($value) < $ruler['min']){
                self::error('value_too_less', '值不能少于 '.$ruler['min'].' 位字符', $name, $ruler);
            }
            if($ruler['max'] && mb_strlen($value) > $ruler['max']){
                self::error('value_too_large', '值不能多于 '.$ruler['max'].' 位字符', $name, $ruler);
            }
        }

        return $value;
    }

    /**
     * 尝试将字符串当json、xml进行解析
     *
     * @param $str
     * @return array|null
     */
    private static function tryParseJsonXml($str)
    {
        $arr = null;
        if(substr($str, 0, 1) === '{' && substr($str, -1, 1) === '}'){
            $arr = json_decode($str, true);
        }elseif(substr($str, 0, 1) === '<' && substr($str, -1, 1) === '>'){//尝试解析xml
            $parsed = simplexml_load_string($str);
            if($parsed){
                $arr = (array)$parsed;
            }
        }

        return $arr;
    }

    /**
     * 遍历转换输入的值
     *
     * @param array|string $value
     * @param array        $ruler
     * @return array
     */
    private static function revisionValue($value, $ruler)
    {
        $arr = [];
        if(is_string($value)){
            $arr = self::tryParseJsonXml($value);
            if($arr === null && $ruler['type'] === 'list'){
                $arr = preg_split('/ *, */', $value);
            }
        }elseif(is_array($value)){
            $arr = $value;
        }

        if($ruler['type'] === 'object'){
            foreach($ruler['nodes'] as $name => $node){
                if(isset($node['use'])){
                    $arr[$name] = $arr[$node['use']];
                }
            }
        }elseif($ruler['type'] === 'objectList'){
            foreach($ruler['nodes'] as $name => $node){
                if(isset($node['use'])){
                    foreach($arr as &$item){
                        $item[$name] = $item[$node['use']];
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 数据类型转换
     *
     * @param mixed  $value
     * @param string $type
     * @param string $format
     *
     * @return mixed
     */
    static public function convert($value, $type, $format = null)
    {
        if($value === null){
            return null;
        }
        switch($type){
            case 'string':
                return (string)$value;
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'double':
                return doubleval($value);
            case 'bool':
                return in_array(strtolower($value), self::FALSE_VALUE)? false: boolval(is_numeric($value)? floatval($value): $value);
            case 'timestamp':
                if(is_numeric($value)){
                    return intval($value);
                }

                return strtotime($value);
            case 'datetime':
                $time = is_numeric($value)? intval($value): strtotime($value);
                if($time === 0){
                    return '';
                }

                return date($format?: 'Y-m-d H:i:s', $time);
            case 'money':
                return money_format('%.2n', $value);
            default:
                return $value;
        }
    }

    /**
     * 值类型检查
     *
     * @param $value
     * @param $type
     * @return bool
     */
    static protected function typeCheck($value, $type)
    {
        switch($type){
            case 'numeric':
                return is_numeric($value);
                break;
            case 'json':
                json_decode($value);

                return json_last_error() !== JSON_ERROR_SYNTAX;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * 获取输入的值
     *
     * @param $str
     * @return array
     */
    private static function parseInput($str)
    {
        $str  = urldecode($str);
        $data = self::tryParseJsonXml($str);
        if($data === null){
            parse_str($str, $data);
        }

        return $data;
    }

    /**
     * 从输入创建参数获取器
     *
     * @param array  $ruler
     * @param string $source
     *
     * @return \jt\Requester
     */
    public static function createFromRequest(array $ruler, $source)
    {
        $data = [];
        if($source === 'query' || $source === 'request'){
            $data = self::parseInput(htmlspecialchars_decode($_SERVER['QUERY_STRING']));
        }

        if($source === 'body' || $source === 'request'){
            if($_SERVER['CONTENT_TYPE'] === 'multipart/form-data'){
                $input = $_POST;
            }else{
                $input = self::parseInput(file_get_contents('php://input'));
            }
            $data = array_replace($data, $input);
        }

        return static::create($data, $ruler, $source);
    }

    /**
     * 清除PHP自动获取的参数，避免被不安全地错误引用
     */
    public static function cleanOriginRequest()
    {
        $_GET  = [];
        $_POST = [];
    }

    /**
     * 创建实例
     *
     * @param        $data
     * @param array  $ruler
     * @param string $method
     *
     * @return \jt\Requester
     */
    public static function create($data, $ruler = [], $method = '')
    {
        $requester             = new self();
        $requester->originData = $data;
        $requester->method     = $method;
        $requester->ruler      = $ruler;

        return $requester;
    }

    /**
     * 验证并给出参数值
     * 当值不存在或值为null时，不满足require要求，要求不许为空需要require与min联合使用
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if(!isset($this->valueCache[$name])){
            $this->valueCache[$name] = $this->value($name, $this->getRuler($name));
        }

        return $this->valueCache[$name];
    }

    /**
     * 获取验证规则
     *
     * @param $name
     *
     * @return array|null
     */
    private function getRuler($name)
    {
        $ruler = [];
        if($name && isset($this->ruler[$name])){
            $ruler = $this->ruler[$name];
            if(is_string($ruler)){
                $ruler              = self::parseValidate($ruler, $name);
                $this->ruler[$name] = $ruler;
            }
        }

        return $ruler;
    }

    /**
     * 批量获取值
     *
     * @param string $names
     *
     * @return array
     */
    public function fetch($names = '*')
    {
        $data = [];
        $ns   = preg_split('/ *, */', $names);
        foreach($ns as $n){
            if($n === '*'){
                foreach($this->ruler as $n => $r){
                    $data[$n] = $this->__get($n);
                }
            }else{
                if(strpos($n, ' as ') > 0){
                    list($field, $n) = preg_split('/ +as +/', $n, 2);
                    $data[$n] = $this->__get($field);
                }else{
                    $data[$n] = $this->__get($n);
                }
            }
        }

        return $data;
    }

    /**
     * 搜集输入名与输出名不一致的字段内容
     *
     */
    protected function collectAsMap()
    {
        if(is_array($this->useNameMap)){
            return;
        }

        $this->useNameMap = [];
        foreach($this->ruler as $name => $ruler){
            if(\strpos($name, ':')){
                list(, $name) = explode(':', $name);
            }
            $option = $this->getRuler($name);
            if(isset($option['use'])){
                $this->useNameMap[$option['use']] = $name;
            }
        }
    }

    /**
     * 获取所有的值(忽略null,当值为null或不符合验证规则时会自动忽略)
     *
     * @param bool $strict 是否严格模式,在非严格模式下遇到参数不合规不报错，只是返回null
     * @return array
     * @throws \jt\Exception
     */
    public function fetchAll($strict = true)
    {
        $data = [];
        $this->collectAsMap();
        try{
            foreach($this->originData as $input => $value){
                $name        = isset($this->useNameMap[$input])? $this->useNameMap[$input]: $input;
                $data[$name] = $this->__get($name);
            }
            //检查是否含有必填项
            foreach($this->ruler as $name => $ruler){
                if(isset($ruler['require']) && !isset($data[$name])){
                    self::error('value_empty', '该项值必填', $name, $ruler);
                }
            }
        }catch(Exception $e){
            if($strict){
                throw $e;
            }
        }

        return $data;
    }

    /**
     * 获取指定列表之外的数据
     *
     * @param string $names 要排除的字段列表(支持逗号分隔)
     *
     * @return array
     */
    public function fetchExclude($names)
    {
        $data    = $this->fetchAll(false);
        $exclude = preg_split('/ *, */', $names);
        foreach($exclude as $e){
            if(isset($data[$e])){
                unset($data[$e]);
            }
        }

        return $data;
    }

    /**
     * 获取分页参数 如果未定义，返回默认值
     *
     * @param int $pageSize 默认每页条数,当客户端未传值时起作用
     * @param int $page 默认当前页数，当客户端未传值时起作用
     *
     * @return array
     */
    public function fetchPage($pageSize = 10, $page = 1)
    {
        $option             = $this->fetch('page, pageSize');
        $option['pageSize'] = $option['pageSize']? intval($option['pageSize']): $pageSize;
        $option['page']     = $option['page']? intval($option['page']): $page;

        return $option;
    }

    /**
     * 对获得的值进行安全化处理
     *
     * @param $value
     * @return mixed
     */
    public static function safeProcess($value)
    {
        if(is_array($value)){
            foreach($value as &$v){
                $v = self::safeProcess($v);
            }
        }elseif(is_string($value)){
            $value = htmlspecialchars($value, ENT_QUOTES);
        }elseif($value === null){
            $value = '';
        }

        return $value;
    }

    /**
     * 获取值
     *
     * @param string $name 值名称
     * @param array  $ruler 验证规则
     *
     * @return mixed
     */
    protected function value($name, $ruler)
    {
        $field = $name;
        if($ruler && isset($ruler['use'])){
            $field = $ruler['use'];
        }

        $value = isset($this->originData[$field])? $this->originData[$field]: null;
        //需要对$value进行处理 防xss攻击
        if($value){
            if($ruler){
                $value = self::validate($value, $ruler, $name);
            }else{
                $value = self::safeProcess($value);
            }
        }

        return $value;
    }

    /**
     * 是否包含该值
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->originData[$name]);
    }

    /**
     * 输出错误
     *
     * @param string $code 错误编码
     * @param string $msg 错误消息
     * @param string $name 值名称
     * @param array  $option
     * @throws Exception
     */
    private static function error($code, $msg, $name, array $option)
    {
        $field = isset($option['_desc'])? $name.':'.$option['_desc']: $name;
        $msg   = '['.$field.'] '.$msg;
        if(RUN_MODE !== 'production'){
            $line = isset($option['_line'])? ' At line '.$option['_line']: '';
            $msg  .= '.'.$line;
        }

        $e = new Exception('inputIll:'.$msg);
        $e->addData(['field' => $name, 'code' => $code]);
        throw $e;
    }

    /**
     * 该列表中的值至少需要一项
     *
     * @param array  $depend
     * @param string $msg
     *
     * @return bool
     */
    public function needOne(array $depend, $msg = '')
    {
        foreach($depend as $name){
            if($this->has($name) && $this->get($name)){
                return true;
            }
        }
        if($msg){
            static::error('require_value', $msg, implode(', ', $depend), []);
        }

        return false;
    }

    /**
     * 剥一块数据出来，保留验证规则，以便传递给其它方法使用
     *
     * @param string $names
     * @return Requester
     */
    public function peel($names)
    {
        $data = $this->fetch($names);

        return self::create($data);
    }

    /**
     * 按列表顺序取出第一个非空值
     *
     * @param array ...$names
     * @return mixed
     */
    public function firstNotEmpty(...$names)
    {
        foreach($names as $name){
            if($this->has($name)){
                return $this->__get($name);
            }
        }

        return null;
    }

    /**
     * 填充默认值
     *
     * @param $ruler
     * @return mixed
     */
    public static function fillEmpty($ruler)
    {
        switch($ruler['type']){
            case 'string':
                return '';
            case 'bool':
                return false;
            case 'object':
                $buffer = [];
                if(isset($ruler['nodes'])){
                    foreach($ruler['nodes'] as $name => $r){
                        $buffer[$name] = self::fillEmpty($r);
                    }
                }

                return $buffer;
            case 'objectList':
            case 'list':
                return [];
            default:
                return 0;
        }
    }

    /**
     * 自动根据接口声明的内容和类型换回数据
     *
     * @param mixed $data
     * @param array $ruler
     *
     * @return mixed
     */
    public static function revisionOutput($data, $ruler)
    {
        return self::validate($data, $ruler, 'out: ', false);
    }

    /**
     * 将Header中的数据进行安全转义处理
     */
    public static function safeHeader()
    {
        foreach($_SERVER as $key => $value){
            $_SERVER[$key] = htmlspecialchars($value, ENT_QUOTES);
        }
    }
}